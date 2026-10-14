<?php

use App\Enums\DocumentStatus;
use App\Enums\SupplierDocumentType;
use App\Enums\UserRole;
use App\Enums\VerificationStatus;
use App\Filament\Resources\SupplierProfiles\Pages\ViewSupplierProfile;
use App\Filament\Resources\SupplierProfiles\RelationManagers\DocumentsRelationManager;
use App\Models\SupplierDocument;
use App\Models\SupplierProfile;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;

beforeEach(function (): void {
    seedReferenceData();
    Storage::fake(config('filesystems.documents_disk'));
    Storage::fake('public');

    $this->supplier = User::factory()->supplier()->create();
    $this->profile = $this->supplier->supplierProfile;
});

function uploadDocument(User $supplier, string $type = 'commercial_registration'): TestResponse
{
    return test()->actingAs($supplier)->post(route('supplier.documents.store'), [
        'type' => $type,
        'file' => UploadedFile::fake()->create('document.pdf', 200, 'application/pdf'),
    ]);
}

it('lets a supplier upload a legal document to the private disk for review', function (): void {
    uploadDocument($this->supplier)
        ->assertRedirect(route('supplier.account', ['tab' => 'documents']))
        ->assertSessionHas('status', __('supplier.document_uploaded'));

    $document = $this->profile->documents()->sole();
    $media = $document->getFirstMedia('file');

    expect($document->type)->toBe(SupplierDocumentType::CommercialRegistration)
        ->and($document->status)->toBe(DocumentStatus::Pending)
        ->and($media->disk)->toBe(config('filesystems.documents_disk'));

    Storage::disk($media->disk)->assertExists($media->getPathRelativeToRoot());
    Storage::disk('public')->assertMissing($media->getPathRelativeToRoot());
});

it('replaces a rejected document and sends it back for review', function (): void {
    $document = SupplierDocument::factory()->create([
        'supplier_profile_id' => $this->profile->id,
        'status' => DocumentStatus::Rejected,
        'note' => 'Blurry scan.',
    ]);

    uploadDocument($this->supplier)->assertRedirect();

    expect($this->profile->documents()->count())->toBe(1)
        ->and($document->fresh()->status)->toBe(DocumentStatus::Pending)
        ->and($document->fresh()->note)->toBeNull()
        ->and($document->fresh()->getMedia('file'))->toHaveCount(1);
});

it('puts a rejected supplier back into the verification queue when they re-upload', function (): void {
    $supplier = User::factory()
        ->has(SupplierProfile::factory()->rejected(), 'supplierProfile')
        ->create(['role' => UserRole::Supplier]);

    uploadDocument($supplier, 'tax_card')->assertRedirect();

    expect($supplier->supplierProfile->fresh()->verification_status)->toBe(VerificationStatus::Pending);
});

it('accepts only legal document types and safe file formats', function (): void {
    $this->actingAs($this->supplier)
        ->post(route('supplier.documents.store'), [
            'type' => 'logo',
            'file' => UploadedFile::fake()->create('logo.pdf', 10, 'application/pdf'),
        ])
        ->assertSessionHasErrors('type');

    $this->actingAs($this->supplier)
        ->post(route('supplier.documents.store'), [
            'type' => 'tax_card',
            'file' => UploadedFile::fake()->create('tax.exe', 10, 'application/octet-stream'),
        ])
        ->assertSessionHasErrors('file');

    expect($this->profile->documents()->count())->toBe(0);
});

it('lists every required document on the documents tab with an upload form', function (): void {
    $this->actingAs($this->supplier)
        ->get(route('supplier.account', ['tab' => 'documents']))
        ->assertOk()
        ->assertSee(SupplierDocumentType::CommercialRegistration->label())
        ->assertSee(SupplierDocumentType::TaxCard->label())
        ->assertSee(__('supplier.document_missing'))
        ->assertSee(route('supplier.documents.store'), false);
});

it('streams a document only to its owner and to admins', function (): void {
    uploadDocument($this->supplier);
    $document = $this->profile->documents()->sole();
    $url = route('supplier-documents.show', $document);

    $this->actingAs($this->supplier)->get($url)->assertOk();
    $this->actingAs(User::factory()->admin()->create())->get($url)->assertOk();

    $this->actingAs(User::factory()->supplier()->create())->get($url)->assertForbidden();
    $this->actingAs(User::factory()->buyer()->create())->get($url)->assertForbidden();

    auth()->logout();
    $this->get($url)->assertRedirect(route('login'));
});

it('lets an admin reject a document with a note the supplier sees', function (): void {
    uploadDocument($this->supplier);
    $document = $this->profile->documents()->sole();

    Livewire::actingAs(User::factory()->admin()->create())
        ->test(DocumentsRelationManager::class, ['ownerRecord' => $this->profile, 'pageClass' => ViewSupplierProfile::class])
        ->assertCanSeeTableRecords([$document])
        ->callAction(TestAction::make('reject_document')->table($document), data: ['note' => 'Expired registration.']);

    expect($document->fresh()->status)->toBe(DocumentStatus::Rejected)
        ->and($document->fresh()->note)->toBe('Expired registration.');

    $this->actingAs($this->supplier)
        ->get(route('supplier.account', ['tab' => 'documents']))
        ->assertSee('Expired registration.');
});

it('lets an admin accept a document', function (): void {
    $document = SupplierDocument::factory()->create(['supplier_profile_id' => $this->profile->id, 'note' => 'Old note']);

    Livewire::actingAs(User::factory()->admin()->create())
        ->test(DocumentsRelationManager::class, ['ownerRecord' => $this->profile, 'pageClass' => ViewSupplierProfile::class])
        ->callAction(TestAction::make('accept_document')->table($document));

    expect($document->fresh()->status)->toBe(DocumentStatus::Accepted)
        ->and($document->fresh()->note)->toBeNull();
});
