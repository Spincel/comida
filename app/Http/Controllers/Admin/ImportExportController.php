<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Area;
use App\Models\Provider;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Schema;

class ImportExportController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Utilities/DataManagement', [
            'stats' => [
                'users' => User::count(),
                'areas' => Area::count(),
                'providers' => Provider::count(),
            ],
            'providers' => Provider::all(['id', 'name']),
        ]);
    }

    /**
     * Generate and download a SQL database dump compatible with SQLite/MySQL.
     */
    public function backup()
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        return new StreamedResponse(function () use ($driver) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "-- Comedor System SQL Backup\n");
            fwrite($handle, "-- Generated on: " . now()->toDateTimeString() . "\n");
            fwrite($handle, "-- Driver: {$driver}\n\n");

            if ($driver === 'sqlite') {
                // SQLite Backup Logic
                fwrite($handle, "PRAGMA foreign_keys = OFF;\n\n");
                $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
                foreach ($tables as $table) {
                    $tableName = $table->name;
                    $createTable = DB::select("SELECT sql FROM sqlite_master WHERE type='table' AND name = ?", [$tableName])[0];
                    fwrite($handle, "DROP TABLE IF EXISTS `{$tableName}`;\n");
                    fwrite($handle, $createTable->sql . ";\n\n");

                    $rows = DB::table($tableName)->get();
                    foreach ($rows as $row) {
                        $rowArray = (array) $row;
                        $columns = array_keys($rowArray);
                        $escapedValues = array_map(fn($v) => is_null($v) ? 'NULL' : "'".str_replace("'", "''", $v)."'", array_values($rowArray));
                        fwrite($handle, "INSERT INTO `{$tableName}` (`".implode('`, `', $columns)."`) VALUES (".implode(', ', $escapedValues).");\n");
                    }
                    fwrite($handle, "\n");
                }
                fwrite($handle, "PRAGMA foreign_keys = ON;\n");
            } else {
                // MySQL Backup Logic
                fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");
                $dbName = config("database.connections.{$connection}.database");
                $tables = DB::select('SHOW TABLES');
                $key = "Tables_in_{$dbName}";
                foreach ($tables as $table) {
                    $tableName = $table->$key;
                    $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`")[0];
                    fwrite($handle, "DROP TABLE IF EXISTS `{$tableName}`;\n");
                    fwrite($handle, $createTable->{'Create Table'} . ";\n\n");

                    $rows = DB::table($tableName)->get();
                    foreach ($rows as $row) {
                        $rowArray = (array) $row;
                        $escapedValues = array_map(fn($v) => is_null($v) ? 'NULL' : "'".str_replace("'", "''", $v)."'", array_values($rowArray));
                        fwrite($handle, "INSERT INTO `{$tableName}` (`".implode('`, `', array_keys($rowArray))."`) VALUES (".implode(', ', $escapedValues).");\n");
                    }
                }
                fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
            }
            fclose($handle);
        }, 200, [
            'Content-Type' => 'application/sql',
            'Content-Disposition' => 'attachment; filename="comedor_full_backup_'.now()->format('Y-m-d_His').'.sql"',
        ]);
    }

    /**
     * Import a SQL backup file.
     */
    public function importSql(Request $request)
    {
        $request->validate(['file' => 'required|file']);
        $sql = file_get_contents($request->file('file')->getRealPath());

        DB::beginTransaction();
        try {
            // Remove comments and split by semicolon (naive split, but works for standard dumps)
            $queries = array_filter(array_map('trim', explode(";\n", $sql)));
            
            // For SQLite compatibility, ensure we enable/disable FKs
            if (config('database.default') === 'sqlite') {
                DB::statement('PRAGMA foreign_keys = OFF');
            } else {
                DB::statement('SET FOREIGN_KEY_CHECKS = 0');
            }

            foreach ($queries as $query) {
                if (!empty($query)) DB::unprepared($query . ';');
            }

            if (config('database.default') === 'sqlite') {
                DB::statement('PRAGMA foreign_keys = ON');
            } else {
                DB::statement('SET FOREIGN_KEY_CHECKS = 1');
            }

            DB::commit();
            return back()->with('success', 'Base de datos restaurada correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error en restauración: ' . $e->getMessage());
        }
    }

    public function import(Request $request)
    {
        ini_set('max_execution_time', 300);
        $request->validate([
            'type' => 'required|in:users,areas,providers',
            'file' => 'required|file',
        ]);

        $rawData = array_map('str_getcsv', file($request->file('file')->getRealPath()));
        if (empty($rawData)) return back()->with('error', 'Archivo vacío.');

        $header = array_map(fn($h) => Str::slug(trim($h), '_'), array_shift($rawData));
        $count = 0;

        DB::beginTransaction();
        try {
            foreach ($rawData as $row) {
                if (count($row) !== count($header)) continue;
                $row = array_combine($header, $row);
                
                if ($request->type === 'areas') {
                    Area::firstOrCreate(['name' => mb_strtoupper(trim($row['nombre']), 'UTF-8')]);
                } elseif ($request->type === 'providers') {
                    Provider::updateOrCreate(['name' => mb_strtoupper(trim($row['nombre']), 'UTF-8')], [
                        'contact_person' => $row['contacto'] ?? null,
                        'contact_phone' => $row['telefono'] ?? null,
                        'address' => $row['direccion'] ?? null,
                    ]);
                } elseif ($request->type === 'users') {
                    User::create([
                        'username' => $row['usuario'] ?? Str::random(8),
                        'first_name' => $row['nombre'],
                        'last_name' => $row['apellido_paterno'] ?? '',
                        'email' => $row['email'] ?? Str::random(5).'@comedor.local',
                        'password' => Hash::make($row['password'] ?? 'password123'),
                        'role' => $row['rol'] ?? 'diner',
                    ]);
                }
                $count++;
            }
            DB::commit();
            return back()->with('success', "Se importaron $count registros.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', "Error: " . $e->getMessage());
        }
    }

    public function truncate(Request $request)
    {
        $request->validate(['type' => 'required']);
        $type = $request->type;

        if ($type === 'all') {
            User::where('id', '!=', auth()->id())->delete();
            Area::whereDoesntHave('users')->delete();
            Provider::query()->delete();
            DB::statement('DELETE FROM orders');
            DB::statement('DELETE FROM provider_daily_statuses');
        } else if ($type === 'sessions') {
            DB::statement('DELETE FROM orders');
            DB::statement('DELETE FROM provider_daily_statuses');
        } else if ($type === 'users') {
            User::where('id', '!=', auth()->id())->delete();
        }

        return back()->with('success', 'Limpieza completada.');
    }
}
