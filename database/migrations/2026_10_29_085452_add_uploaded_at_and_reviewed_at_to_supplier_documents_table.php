<?php

use App\Enums\DocumentStatus;
use App\Models\SupplierDocument;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The admin's accept/reject touched updated_at, which the tables showed as
     * the upload time. Upload and review now each get their own timestamp.
     * Existing rows take the upload time from their current file, and a
     * reviewed row takes its last update as the review time.
     */
    public function up(): void
    {
        Schema::table('supplier_documents', function (Blueprint $table) {
            $table->timestamp('uploaded_at')->nullable()->after('note');
            $table->timestamp('reviewed_at')->nullable()->after('uploaded_at');
        });

        $latestFileUpload = DB::table('media')
            ->selectRaw('max(media.created_at)')
            ->where('media.model_type', SupplierDocument::class)
            ->where('media.collection_name', 'file')
            ->whereColumn('media.model_id', 'supplier_documents.id');

        DB::table('supplier_documents')->update([
            'uploaded_at' => DB::raw('coalesce(('.$latestFileUpload->toRawSql().'), supplier_documents.created_at)'),
        ]);

        DB::table('supplier_documents')
            ->where('status', '!=', DocumentStatus::Pending->value)
            ->update(['reviewed_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('supplier_documents', function (Blueprint $table) {
            $table->dropColumn(['uploaded_at', 'reviewed_at']);
        });
    }
};
