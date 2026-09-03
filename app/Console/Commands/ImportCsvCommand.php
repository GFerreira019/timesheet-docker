<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Exception;

class ImportCsvCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:csv {table} {file} {--delimiter=; : Delimitador do arquivo CSV (padrão ;)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importa dados de um arquivo CSV para uma tabela do PostgreSQL de forma segura (linha a linha)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $table = $this->argument('table');
        $filePath = $this->argument('file');
        $delimiter = $this->option('delimiter') ?: ';';

        if (!file_exists($filePath) || !is_readable($filePath)) {
            $this->error("Erro: O arquivo '{$filePath}' não foi encontrado ou não tem permissão de leitura.");
            return Command::FAILURE;
        }

        $this->info("Iniciando importação para a tabela: {$table} (Delimitador: '{$delimiter}')");

        $fileHandle = fopen($filePath, 'r');
        
        // Extrai os cabeçalhos (primeira linha)
        $headers = fgetcsv($fileHandle, 0, $delimiter);
        
        if (!$headers) {
            $this->error("Falha ao ler o cabeçalho do arquivo CSV.");
            fclose($fileHandle);
            return Command::FAILURE;
        }

        // Limpa caracteres especiais invisíveis (BOM UTF-8) e espaços dos cabeçalhos
        $headers = array_map(function($header) {
            // Remove BOM explicitamente (\xEF\xBB\xBF ou UTF-8 FEFF)
            $clean = preg_replace('/^\xEF\xBB\xBF/', '', $header);
            $clean = preg_replace('/[\x{FEFF}]/u', '', $clean);
            return trim(preg_replace('/[\x00-\x1F\x7F\xA0]/u', '', $clean));
        }, $headers);

        $hasIdColumn = in_array('id', $headers);
        
        // Contar o total de linhas para a Progress Bar
        $totalLines = 0;
        while (!feof($fileHandle)) {
            $line = fgets($fileHandle);
            if ($line !== false && trim($line) !== '') {
                $totalLines++;
            }
        }
        
        // Volta o ponteiro do arquivo para a linha de dados
        rewind($fileHandle);
        fgetcsv($fileHandle, 0, $delimiter); // Pula o cabeçalho
        
        $bar = $this->output->createProgressBar($totalLines);
        $bar->start();

        $errors = [];
        $rowNumber = 2; // Começa na linha 2
        $successCount = 0;

        while (($row = fgetcsv($fileHandle, 0, $delimiter)) !== false) {
            // Ignora linhas totalmente vazias
            if (empty(array_filter($row, fn($v) => trim((string)$v) !== ''))) {
                $rowNumber++;
                continue; 
            }

            // Verifica se a linha tem o mesmo número de colunas do cabeçalho
            if (count($headers) !== count($row)) {
                $errors[] = [
                    'linha' => $rowNumber,
                    'erro'  => 'Desalinhamento de colunas: A linha não bate com a quantidade de cabeçalhos.'
                ];
                $rowNumber++;
                $bar->advance();
                continue;
            }

            // Monta o array associativo [ 'coluna' => 'valor' ]
            $data = array_combine($headers, $row);
            
            // Remove qualquer chave com valor vazio ou nulo (permite ao Postgres aplicar defaults e auto-incremento)
            $data = array_filter($data, function($value) {
                if (is_string($value)) {
                    $value = trim($value);
                }
                return $value !== '' && $value !== null;
            });

            // Tenta inserir no banco
            try {
                DB::table($table)->insert($data);
                $successCount++;
            } catch (Exception $e) {
                // Guarda o erro de forma formatada e segue a execução
                $errors[] = [
                    'linha' => $rowNumber,
                    'erro'  => substr($e->getMessage(), 0, 100) . '...' // Reduz a string para não quebrar a tabela no console
                ];
            }

            $rowNumber++;
            $bar->advance();
        }

        fclose($fileHandle);
        $bar->finish();
        $this->newLine(2);

        $this->info("Importação finalizada! {$successCount} linha(s) inserida(s) com sucesso.");

        // Relatório de erros
        if (count($errors) > 0) {
            $this->error("Houve falha na importação de " . count($errors) . " linha(s):");
            $this->table(['Linha (CSV)', 'Erro (Exception)'], $errors);
        }

        // Proteção do PostgreSQL (IDs Incrementeis)
        if ($hasIdColumn && $successCount > 0) {
            $this->info("Atualizando sequence da tabela...");
            try {
                // O coalesce evita erros caso a tabela estivesse vazia antes
                DB::statement("SELECT setval(pg_get_serial_sequence('{$table}', 'id'), coalesce(max(id),0) + 1, false) FROM {$table}");
                $this->info("Sequence atualizada com sucesso!");
            } catch (Exception $e) {
                // Fallback mais genérico se pg_get_serial_sequence falhar
                try {
                    DB::statement("SELECT setval('{$table}_id_seq', (SELECT MAX(id) FROM {$table}))");
                    $this->info("Sequence atualizada com sucesso pelo método secundário!");
                } catch (Exception $e2) {
                    $this->warn("Não foi possível atualizar a Sequence automaticamente. Talvez sua tabela não tenha auto-incremento configurado como SERIAL no PG.");
                }
            }
        }

        return Command::SUCCESS;
    }
}
