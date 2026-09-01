<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ErpObraManual extends Model
{
    /**
     * Tabela associada ao model.
     *
     * @var string
     */
    protected $table = 'erp_obras_manual';

    /**
     * Os atributos que podem ser atribuídos em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'cliente_codigo',
        'projeto_codigo',
        'projeto_nome',
        'status_ativo',
        'tipo_categoria',
        
        // Novos campos
        'projeto_unidade', 'projeto_objeto', 'setor_id', 'projeto_etapa', 'projeto_status',
        'cronograma_inicio', 'cronograma_fim', 'projeto_avanco',
        'lider_comercial', 'gerente_implantacao', 'gerente_manutencao',
        'target', 'contrato_assinatura', 'termo_entrega',
        'cnpj', 'razao_social', 'endereco', 'cidade', 'pedagio',
        'valor_venda', 'valor_monitoramento', 'valor_licenca', 'valor_manutencao', 'valor_locacao',
        'comentarios',
        
        // Ausências
        'ausencia_cronograma', 'ausencia_contrato', 'ausencia_termo',
    ];

    /**
     * Os acessores a serem adicionados à representação em array do modelo.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'lider_comercial_id',
        'gerente_implantacao_id',
        'gerente_manutencao_id',
    ];

    public function getLiderComercialIdAttribute()
    {
        return $this->attributes['lider_comercial'] ?? null;
    }

    public function getGerenteImplantacaoIdAttribute()
    {
        return $this->attributes['gerente_implantacao'] ?? null;
    }

    public function getGerenteManutencaoIdAttribute()
    {
        return $this->attributes['gerente_manutencao'] ?? null;
    }

    /**
     * Os atributos que devem ser convertidos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status_ativo' => 'boolean',
        'ausencia_cronograma' => 'boolean',
        'ausencia_contrato' => 'boolean',
        'ausencia_termo' => 'boolean',
        'cronograma_inicio' => 'date',
        'cronograma_fim' => 'date',
        'target' => 'date',
        'contrato_assinatura' => 'date',
        'termo_entrega' => 'date',
        'projeto_avanco' => 'float',
        'pedagio' => 'boolean',
        'valor_venda' => 'float',
        'valor_monitoramento' => 'float',
        'valor_licenca' => 'float',
        'valor_manutencao' => 'float',
        'valor_locacao' => 'float',
    ];

    /**
     * Histórico de edições (auditoria).
     */
    public function historicos()
    {
        return $this->hasMany(ControleProjetoHistorico::class, 'projeto_original_id');
    }

    public function setor()
    {
        return $this->belongsTo(Setor::class, 'setor_id');
    }

    public function liderComercial()
    {
        return $this->belongsTo(Colaborador::class, 'lider_comercial');
    }

    public function gerenteImplantacao()
    {
        return $this->belongsTo(Colaborador::class, 'gerente_implantacao');
    }


    public function gerenteManutencao()
    {
        return $this->belongsTo(Colaborador::class, 'gerente_manutencao');
    }


    /**
     * Sincronização de gestores na tabela pivot (colaborador_projeto_gerenciado)
     */
    public function coordenadoresProjeto()
    {
        return $this->belongsToMany(Colaborador::class, 'colaborador_projeto_gerenciado', 'projeto_id', 'colaborador_id');
    }

    /**
     * Register the model events.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::observe(\App\Observers\ErpObraManualObserver::class);
    }
}


