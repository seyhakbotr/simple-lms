<?php

use Illuminate\Support\Facades\Route;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Barryvdh\DomPDF\Facade\Pdf;

Route::get('/', function () {
    if (! auth()->check()) {
        return view('welcome');
    }

    if (auth()->user()->role?->name === 'admin') {
        return redirect('/admin');
    }

    return redirect('/staff');
});

Route::get('/locale/{locale}', function (string $locale) {
    if (! in_array($locale, ['en', 'km'], true)) {
        abort(404);
    }

    if (request()->hasSession()) {
        session(['locale' => $locale]);
    } else {
        cookie()->queue(cookie('locale', $locale, 60 * 24 * 365));
    }

    return redirect()->back();
})->name('locale.switch');

Route::post('/locale/{locale}', function (string $locale) {
    if (! in_array($locale, ['en', 'km'], true)) {
        abort(404);
    }

    if (request()->hasSession()) {
        session(['locale' => $locale]);
    } else {
        cookie()->queue(cookie('locale', $locale, 60 * 24 * 365));
    }

    return redirect()->back();
})->name('locale.switch.post');

// Invoice PDF preview route
Route::get("invoices/{invoice}/pdf", function (Invoice $invoice) {
    $invoiceService = app(InvoiceService::class);
    $data = $invoiceService->getInvoiceData($invoice);

    $pdf = Pdf::loadView("pdf.invoice", ["data" => $data]);

    return $pdf->stream("invoice-{$data["invoice_number"]}.pdf");
})->name("invoices.pdf.preview");
