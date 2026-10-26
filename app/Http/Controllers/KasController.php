<?php

namespace App\Http\Controllers;

use App\Models\KasSetting;
use App\Models\KasTransaksi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KasController extends Controller
{
    private const DISK = 'local';

    public function index(Request $request): View
    {
        abort_unless($request->user()->hasPermission('finance.manage'), 403);

        $setting = KasSetting::current();
        $semua = $this->rows();
        $totalMasuk = (int) $semua->where('tipe', 'masuk')->sum('jumlah');
        $totalKeluar = (int) $semua->where('tipe', 'keluar')->sum('jumlah');
        $sisa = $setting->saldo_awal + $totalMasuk - $totalKeluar;

        $daftarBulan = $semua->map(fn ($t) => $t->tanggal->format('Y-m'))->unique()->sortDesc()->values();
        $bulan = $daftarBulan->contains($request->query('bulan')) ? $request->query('bulan') : null;

        $jalan = $setting->saldo_awal;
        $bawaan = $setting->saldo_awal;
        $baris = [];
        $periodeMasuk = 0;
        $periodeKeluar = 0;

        foreach ($semua as $t) {
            $jalan += $t->delta();
            $ym = $t->tanggal->format('Y-m');
            if (! $bulan || $ym === $bulan) {
                $baris[] = ['t' => $t, 'saldo' => $jalan];
                $t->isMasuk() ? $periodeMasuk += $t->jumlah : $periodeKeluar += $t->jumlah;
            } elseif ($ym < $bulan) {
                $bawaan = $jalan;
            }
        }

        $edit = $request->filled('edit')
            ? $this->rows()->whereKey($request->integer('edit'))->first()
            : null;

        return view('kas.index', [
            'setting' => $setting,
            'semua' => $semua,
            'totalMasuk' => $totalMasuk,
            'totalKeluar' => $totalKeluar,
            'sisa' => $sisa,
            'daftarBulan' => $daftarBulan,
            'bulan' => $bulan,
            'baris' => $baris,
            'periodeMasuk' => $periodeMasuk,
            'periodeKeluar' => $periodeKeluar,
            'saldoMulai' => $bulan ? $bawaan : $setting->saldo_awal,
            'saldoAkhir' => $baris ? end($baris)['saldo'] : ($bulan ? $bawaan : $setting->saldo_awal),
            'edit' => $edit,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('finance.manage'), 403);
        $lines = $this->lines($request);
        $bukti = $request->hasFile('bukti') ? $this->simpanBukti($request) : [];
        DB::transaction(function () use ($lines, $bukti) {
            foreach ($lines as $line) {
                KasTransaksi::query()->create($line + $bukti + [
                    'outlet_id' => current_outlet_id(),
                    'user_id' => Auth::id(),
                ]);
            }
        });
        $label = $lines[0]['tipe'] === 'masuk' ? 'tambah saldo' : 'pembelian';

        return redirect()->route('kas.index')->with('success', count($lines).' '.$label.' tersimpan.');
    }

    public function update(Request $request, KasTransaksi $transaksi): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('finance.manage'), 403);
        $this->owned($transaksi);
        $data = $this->lines($request)[0];
        if ($request->hasFile('bukti')) {
            $this->hapusFileBukti($transaksi);
            $data += $this->simpanBukti($request);
        } elseif ($request->boolean('hapus_bukti')) {
            $this->hapusFileBukti($transaksi);
            $data += ['bukti_path' => null, 'bukti_mime' => null];
        }
        $transaksi->update($data);

        return redirect()->route('kas.index')->with('success', 'Perubahan tersimpan.');
    }

    public function destroy(Request $request, KasTransaksi $transaksi): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('finance.manage'), 403);
        $this->owned($transaksi);
        $this->hapusFileBukti($transaksi);
        $transaksi->delete();

        return back()->with('success', 'Catatan dihapus.');
    }

    public function uploadBukti(Request $request, KasTransaksi $transaksi): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('finance.manage'), 403);
        $this->owned($transaksi);
        $request->validate([
            'bukti' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,heic,pdf', 'max:20480'],
        ], [
            'bukti.mimes' => 'Format bukti tidak didukung. Pakai foto JPG/PNG atau PDF.',
            'bukti.max' => 'File bukti terlalu besar (maksimal 20 MB).',
        ]);
        $this->hapusFileBukti($transaksi);
        $transaksi->update($this->simpanBukti($request));

        return back()->with('success', 'Bukti tersimpan.');
    }

    public function bukti(Request $request, KasTransaksi $transaksi)
    {
        abort_unless($request->user()->hasPermission('finance.manage'), 403);
        $this->owned($transaksi);
        abort_unless($transaksi->bukti_path && Storage::disk(self::DISK)->exists($transaksi->bukti_path), 404);

        return Storage::disk(self::DISK)->response($transaksi->bukti_path, null, [
            'Content-Type' => $transaksi->bukti_mime ?? 'application/octet-stream',
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }

    public function updateSetting(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('finance.manage'), 403);
        $request->merge([
            'saldo_awal' => (int) preg_replace('/\D/', '', (string) $request->input('saldo_awal')),
            'batas_minimum' => (int) preg_replace('/\D/', '', (string) $request->input('batas_minimum')),
        ]);
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:60'],
            'saldo_awal' => ['required', 'integer', 'min:0'],
            'batas_minimum' => ['required', 'integer', 'min:0'],
        ]);
        KasSetting::current()->update($data);

        return back()->with('success', 'Pengaturan kas tersimpan.');
    }

    public function export(Request $request): StreamedResponse
    {
        abort_unless($request->user()->hasPermission('finance.manage'), 403);
        $setting = KasSetting::current();
        $semua = $this->rows();
        $namaFile = (preg_replace('/[^\w\- ]/', '', $setting->nama) ?: 'kas').'.csv';

        return response()->streamDownload(function () use ($setting, $semua) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Tanggal', 'Item', 'Keterangan', 'Tambah saldo', 'Uang keluar', 'Sisa saldo', 'Ada bukti'], ';');
            fputcsv($out, ['', 'Saldo awal', '', '', '', $setting->saldo_awal, ''], ';');
            $jalan = $setting->saldo_awal;
            foreach ($semua as $t) {
                $jalan += $t->delta();
                fputcsv($out, [
                    $t->tanggal->format('Y-m-d'),
                    $t->item,
                    $t->keterangan,
                    $t->isMasuk() ? $t->jumlah : '',
                    $t->isMasuk() ? '' : $t->jumlah,
                    $jalan,
                    $t->bukti_path ? 'Ya' : 'Tidak',
                ], ';');
            }
            fclose($out);
        }, $namaFile, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    protected function rows()
    {
        return KasTransaksi::query()
            ->where('outlet_id', current_outlet_id())
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get();
    }

    protected function owned(KasTransaksi $transaksi): void
    {
        abort_unless((int) $transaksi->outlet_id === (int) current_outlet_id(), 404);
    }

    protected function lines(Request $request): array
    {
        $raw = $request->input('items');
        if (! is_array($raw)) {
            $raw = [[
                'item' => $request->input('item'),
                'keterangan' => $request->input('keterangan'),
                'jumlah' => $request->input('jumlah'),
            ]];
        }

        $items = collect($raw)
            ->map(fn ($row) => [
                'item' => trim((string) ($row['item'] ?? '')),
                'keterangan' => trim((string) ($row['keterangan'] ?? '')),
                'jumlah' => (int) preg_replace('/\D/', '', (string) ($row['jumlah'] ?? '')),
            ])
            ->filter(fn ($row) => $row['item'] !== '' || $row['keterangan'] !== '' || $row['jumlah'] > 0)
            ->values()
            ->all();

        $request->merge(['items' => $items]);

        $data = $request->validate([
            'tanggal' => ['required', 'date'],
            'tipe' => ['required', 'in:keluar,masuk'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item' => ['nullable', 'required_if:tipe,keluar', 'string', 'max:120'],
            'items.*.keterangan' => ['nullable', 'string', 'max:255'],
            'items.*.jumlah' => ['required', 'integer', 'min:1'],
            'bukti' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,heic,pdf', 'max:20480'],
        ], [
            'items.min' => 'Isi minimal satu item.',
            'items.*.item.required_if' => 'Isi item yang dibeli.',
            'items.*.jumlah.min' => 'Isi jumlah uang lebih dari 0.',
            'items.*.jumlah.required' => 'Isi jumlah uang lebih dari 0.',
            'bukti.mimes' => 'Format bukti tidak didukung. Pakai foto JPG/PNG atau PDF.',
            'bukti.max' => 'File bukti terlalu besar (maksimal 20 MB).',
        ]);

        return array_map(fn ($row) => [
            'tanggal' => $data['tanggal'],
            'tipe' => $data['tipe'],
            'item' => $row['item'] !== '' ? $row['item'] : 'Tambah saldo',
            'keterangan' => $row['keterangan'] !== '' ? $row['keterangan'] : null,
            'jumlah' => $row['jumlah'],
        ], $data['items']);
    }

    protected function simpanBukti(Request $request): array
    {
        $file = $request->file('bukti');

        return [
            'bukti_path' => $file->store('bukti-kas/'.now()->format('Y-m'), self::DISK),
            'bukti_mime' => $file->getMimeType(),
        ];
    }

    protected function hapusFileBukti(KasTransaksi $transaksi): void
    {
        if (! $transaksi->bukti_path) {
            return;
        }

        $shared = KasTransaksi::query()
            ->where('bukti_path', $transaksi->bukti_path)
            ->whereKeyNot($transaksi->id)
            ->exists();
        if (! $shared) {
            Storage::disk(self::DISK)->delete($transaksi->bukti_path);
        }
    }
}
