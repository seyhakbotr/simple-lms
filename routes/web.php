<?php

use Illuminate\Support\Facades\Route;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Barryvdh\DomPDF\Facade\Pdf;

Route::get("/", function () {
    return view("welcome");
});

// Invoice PDF preview route
Route::get("invoices/{invoice}/pdf", function (Invoice $invoice) {
    $invoiceService = app(InvoiceService::class);
    $data = $invoiceService->getInvoiceData($invoice);

    $pdf = Pdf::loadView("pdf.invoice", ["data" => $data]);

    return $pdf->stream("invoice-{$data["invoice_number"]}.pdf");
})->name("invoices.pdf.preview");
