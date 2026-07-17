<?php

namespace App\Http\Controllers;

use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MaintenanceController extends Controller
{
    // ── Menu Version Cache Key ────────────────────────────────────────────────
    private const MENU_VERSION_KEY = 'menu_version';

    /**
     * Tampilkan panel maintenance.
     */
    public function index()
    {
        $menuItems = MenuItem::orderBy('sort_order')->get();
        return view('maintenance.index', compact('menuItems'));
    }

    /**
     * Tambah menu item baru.
     */
    public function storeMenu(Request $request)
    {
        $data = $request->validate([
            'label'         => 'required|string|max:100',
            'route_name'    => 'nullable|string|max:100',
            'icon'          => 'required|string|max:30',
            'visible_to'    => 'required|array|min:1',
            'visible_to.*'  => 'in:admin,internship,maintenance',
            'sort_order'    => 'required|integer|min:0',
            'is_active'     => 'boolean',
            'confirm_title' => 'nullable|string|max:150',
            'confirm_desc'  => 'nullable|string|max:300',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        MenuItem::create($data);
        $this->bumpMenuVersion();

        Log::info('Menu item ditambahkan.', [
            'label' => $data['label'],
            'by'    => auth()->user()->name,
        ]);

        return back()->with('success', "Menu \"{$data['label']}\" berhasil ditambahkan.");
    }

    /**
     * Update menu item yang ada.
     */
    public function updateMenu(Request $request, MenuItem $menuItem)
    {
        $data = $request->validate([
            'label'         => 'required|string|max:100',
            'route_name'    => 'nullable|string|max:100',
            'icon'          => 'required|string|max:30',
            'visible_to'    => 'required|array|min:1',
            'visible_to.*'  => 'in:admin,internship,maintenance',
            'sort_order'    => 'required|integer|min:0',
            'is_active'     => 'boolean',
            'confirm_title' => 'nullable|string|max:150',
            'confirm_desc'  => 'nullable|string|max:300',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $menuItem->update($data);
        $this->bumpMenuVersion();

        Log::info('Menu item diperbarui.', [
            'id'    => $menuItem->id,
            'label' => $data['label'],
            'by'    => auth()->user()->name,
        ]);

        return back()->with('success', "Menu \"{$data['label']}\" berhasil diperbarui.");
    }

    /**
     * Hapus menu item.
     */
    public function destroyMenu(MenuItem $menuItem)
    {
        $label = $menuItem->label;
        $menuItem->delete();
        $this->bumpMenuVersion();

        Log::info('Menu item dihapus.', [
            'label' => $label,
            'by'    => auth()->user()->name,
        ]);

        return back()->with('success', "Menu \"{$label}\" berhasil dihapus.");
    }

    /**
     * Toggle aktif/nonaktif menu item.
     */
    public function toggleMenu(MenuItem $menuItem)
    {
        $menuItem->update(['is_active' => !$menuItem->is_active]);
        $this->bumpMenuVersion();

        $status = $menuItem->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Menu \"{$menuItem->label}\" berhasil {$status}.");
    }

    /**
     * SSE Endpoint — mengirim notifikasi realtime ke client saat menu berubah.
     *
     * Client (JavaScript) mendengarkan endpoint ini menggunakan EventSource API.
     * Setiap kali menu_version di cache berubah, event dikirim ke semua client
     * yang terhubung, meminta mereka untuk reload sidebar.
     */
    public function menuUpdates(Request $request)
    {
        // Tutup session sebelum streaming untuk menghindari session lock
        $request->session()->save();

        return response()->stream(function () {
            $lastVersion = Cache::get(self::MENU_VERSION_KEY, 0);
            $startTime   = time();
            $maxDuration = 28; // Reconnect setiap 28 detik (sebelum proxy timeout 30s)

            // Kirim versi awal ke client
            $this->sseEvent('connected', ['version' => $lastVersion]);

            while (time() - $startTime < $maxDuration) {
                if (connection_aborted()) {
                    break;
                }

                $currentVersion = Cache::get(self::MENU_VERSION_KEY, 0);

                if ($currentVersion !== $lastVersion) {
                    $this->sseEvent('menu_updated', ['version' => $currentVersion]);
                    $lastVersion = $currentVersion;
                }

                // Heartbeat agar koneksi tidak timeout
                echo ": heartbeat\n\n";
                ob_flush();
                flush();

                sleep(3);
            }

            // Signal reconnect
            $this->sseEvent('reconnect', ['version' => $lastVersion]);
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache, no-store',
            'X-Accel-Buffering' => 'no',  // Matikan nginx buffering
            'Connection'        => 'keep-alive',
        ]);
    }

    /**
     * API endpoint — kembalikan versi menu saat ini (untuk polling fallback).
     */
    public function menuVersion()
    {
        return response()->json([
            'version' => Cache::get(self::MENU_VERSION_KEY, 0),
        ]);
    }

    /**
     * Naikkan versi menu di cache agar semua client SSE mendapat notifikasi.
     */
    private function bumpMenuVersion(): void
    {
        Cache::put(self::MENU_VERSION_KEY, now()->timestamp, 3600);
    }

    /**
     * Format dan kirim SSE event.
     */
    private function sseEvent(string $event, array $data): void
    {
        echo "event: {$event}\n";
        echo "data: " . json_encode($data) . "\n\n";
        ob_flush();
        flush();
    }
}
