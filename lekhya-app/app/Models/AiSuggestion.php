<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AiSuggestion extends Model {
    protected $table = 'ai_suggestions';
    protected $fillable = ['tenant_id','type','input_context','suggestion','status','reviewed_by','reviewed_at','model_used','model_metadata','invoice_id','journal_id'];
    protected $casts = ['input_context'=>'array','suggestion'=>'array','model_metadata'=>'array','reviewed_at'=>'datetime'];
    public function reviewer() { return $this->belongsTo(User::class, 'reviewed_by'); }
}
