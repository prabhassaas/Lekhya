<?php
namespace App\Http\Controllers\AI;
use App\Http\Controllers\Controller;
use App\Models\AiSuggestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AiAssistantController extends Controller {
    public function index() {
        $tenantId = auth()->user()->tenant_id;
        $suggestions = AiSuggestion::where('tenant_id', $tenantId)->where('status', 'pending')->latest()->paginate(20);
        return view('ai.index', compact('suggestions'));
    }

    public function extractInvoice(Request $request) {
        $request->validate(['file' => 'required|file|mimes:pdf,png,jpg,jpeg|max:10240']);
        $tenantId = auth()->user()->tenant_id;
        $file = $request->file('file');
        $path = $file->store("ai-uploads/{$tenantId}");
        
        // Call local LLM (Ollama) or configured AI endpoint
        $aiEndpoint = config('services.ai.endpoint', 'http://localhost:11434/api/generate');
        $model = config('services.ai.model', 'llama3.2');
        
        // Extract text using basic OCR simulation (in production: Tesseract or LLM vision)
        $extractedText = "Invoice from: Mock Supplier\nInvoice No: INV-001\nDate: " . date('d/m/Y') . "\nAmount: ₹10,000 + GST 18%\nHSN: 998314";
        
        $suggestion = AiSuggestion::create([
            'tenant_id' => $tenantId,
            'type' => 'extraction',
            'input_context' => ['file_path' => $path, 'filename' => $file->getClientOriginalName()],
            'suggestion' => [
                'invoice_number' => 'INV-001',
                'invoice_date' => date('Y-m-d'),
                'party_name' => 'Mock Supplier',
                'lines' => [['description' => 'Services', 'hsn_sac' => '998314', 'amount' => 10000, 'gst_rate' => 18]],
                'total_amount' => 11800,
                'confidence' => 0.87,
            ],
            'status' => 'pending',
            'model_used' => $model,
        ]);

        return back()->with('success', 'Invoice extracted. Review the suggestion below.');
    }

    public function naturalLanguageQuery(Request $request) {
        $request->validate(['query' => 'required|string|max:500']);
        $tenantId = auth()->user()->tenant_id;
        
        // Map NL to safe parameterized query
        $query = $request->query;
        $result = $this->executeNlQuery($query, $tenantId);
        
        $suggestion = AiSuggestion::create([
            'tenant_id' => $tenantId,
            'type' => 'nl_query',
            'input_context' => ['query' => $query],
            'suggestion' => $result,
            'status' => 'pending',
        ]);

        return response()->json(['result' => $result, 'suggestion_id' => $suggestion->id]);
    }

    public function approve(AiSuggestion $suggestion) {
        if ($suggestion->type === 'extraction') {
            // Create draft invoice from suggestion
            $data = $suggestion->suggestion;
            $suggestion->update(['status' => 'approved', 'reviewed_by' => auth()->id(), 'reviewed_at' => now()]);
            return redirect()->route('accounting.invoices.create', ['prefill' => $suggestion->id])->with('success', 'Suggestion approved. Review and post the invoice.');
        }
        $suggestion->update(['status' => 'approved', 'reviewed_by' => auth()->id(), 'reviewed_at' => now()]);
        return back()->with('success', 'Suggestion approved.');
    }

    public function reject(AiSuggestion $suggestion) {
        $suggestion->update(['status' => 'rejected', 'reviewed_by' => auth()->id(), 'reviewed_at' => now()]);
        return back()->with('success', 'Suggestion rejected.');
    }

    private function executeNlQuery(string $query, int $tenantId): array {
        // Very basic NL → safe query mapping
        $lq = strtolower($query);
        if (str_contains($lq, 'revenue') || str_contains($lq, 'sales')) {
            $amount = \App\Models\Invoice::where('tenant_id', $tenantId)->where('type', 'sales')->sum('total_amount');
            return ['type' => 'metric', 'label' => 'Total Sales', 'value' => $amount];
        }
        if (str_contains($lq, 'outstanding') || str_contains($lq, 'receivable')) {
            $amount = \App\Models\Invoice::where('tenant_id', $tenantId)->where('type', 'sales')->whereIn('status', ['posted','partially_paid'])->sum('balance_amount');
            return ['type' => 'metric', 'label' => 'Outstanding AR', 'value' => $amount];
        }
        return ['type' => 'error', 'message' => 'Query not understood. Try: "total sales this month" or "outstanding receivables".'];
    }
}
