<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\Ticket;

class SuporteController extends Controller
{
    /**
     * Exibe o dashboard principal da Gestão de Tickets
     */
    public function index(Request $request): View
    {
        // Contadores Globais (sem filtro)
        $countAbertos = Ticket::where('status', 'ABERTO')->count();
        $countEmAndamento = Ticket::where('status', 'EM_ANDAMENTO')->count();
        $countAguardando = Ticket::where('status', 'AGUARDANDO')->count();
        $countFechados = Ticket::where('status', 'FECHADO')->count();

        // Query para a listagem
        $query = Ticket::with('user')->latest();

        $query->when($request->search, function ($q) use ($request) {
            $q->where(function ($sub) use ($request) {
                $sub->where('titulo', 'like', "%{$request->search}%")
                    ->orWhere('descricao', 'like', "%{$request->search}%")
                    ->orWhere('id', 'like', "%{$request->search}%");
            });
        });

        $query->when($request->status, function ($q) use ($request) {
            $q->where('status', $request->status);
        });

        $query->when($request->categoria, function ($q) use ($request) {
            $q->where('categoria', $request->categoria);
        });

        $tickets = $query->get();

        return view('suporte.index', compact(
            'tickets', 'countAbertos', 'countEmAndamento', 'countAguardando', 'countFechados'
        ));
    }

    /**
     * Atualiza o status do ticket
     */
    public function updateStatus(Request $request, Ticket $ticket)
    {
        $request->validate([
            'status' => 'required|string|in:ABERTO,EM_ANDAMENTO,AGUARDANDO,FECHADO'
        ]);

        $ticket->status = $request->status;
        $ticket->save();

        return redirect()->back()->with('success', 'Status do ticket atualizado com sucesso!');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'categoria' => 'required|string|max:50',
            'prioridade' => 'required|string|max:50',
            'titulo' => 'required|string|max:200',
            'descricao' => 'required|string|max:5000',
            'anexo' => 'nullable|file|max:5120|mimes:jpeg,png,jpg,webp', // max 5MB
        ]);

        $ticket = new Ticket($validated);
        $ticket->user_id = auth()->id();
        $ticket->status = 'ABERTO';

        if ($request->hasFile('anexo')) {
            // Armazenamento privado, fora da pasta public
            $path = $request->file('anexo')->store('tickets', 'local');
            $ticket->anexo_path = $path;
        }

        $ticket->save();

        return response()->json(['success' => true, 'message' => 'Ticket criado com sucesso!']);
    }

    /**
     * Retorna o arquivo de anexo de um ticket de forma segura
     */
    public function anexo(Ticket $ticket)
    {
        // Garante que o ticket possui um anexo e que o arquivo existe
        if (!$ticket->anexo_path || !\Illuminate\Support\Facades\Storage::disk('local')->exists($ticket->anexo_path)) {
            abort(404, 'Anexo não encontrado.');
        }

        // Retorna o arquivo diretamente do disco local (privado)
        return response()->file(\Illuminate\Support\Facades\Storage::disk('local')->path($ticket->anexo_path));
    }
}
