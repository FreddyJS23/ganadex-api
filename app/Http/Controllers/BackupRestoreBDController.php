<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Process;
use PhpParser\Node\Stmt\TryCatch;
use Throwable;
use ZipArchive;

class BackupRestoreBDController extends Controller
{
    public function respaldarBd()
    {
        Artisan::call('backup:run --only-db');

        $listaBackup = Storage::files('Laravel');

        $pathDbBackup = end($listaBackup);

        return Storage::download($pathDbBackup);
    }
    public function obtenerUltimoRespaldo()
    {
        $listaBackup = Storage::files('Laravel');

        $pathDbRestore = end($listaBackup);
        //format Y-m-d
        $regexDate = '/[1-9][0-9][0-9]{2}-([0][1-9]|[1][0-2])-([1-2][0-9]|[0][1-9]|[3][0-1])/';
        $dateLastBackup = preg_match($regexDate, $pathDbRestore, $mathes);
        $dateLastBackup = $mathes[0] ?? null;
        //$dateLastBackup = null;

        return response()->json(['ultimo_backup' => $dateLastBackup], 200);
    }


    public function restaurarBd(Request $file)
    {

        $dbHost = env('DB_HOST');
        $dbUser = env('DB_USERNAME');
        $dbPass = env('DB_PASSWORD');
        $dbName = env('DB_DATABASE');

        $listaBackup = Storage::files('Laravel');

        $pathDbRestore = end($listaBackup);

        $zip = new ZipArchive();

        $zip->open(storage_path('app/' . $pathDbRestore));

        try {
            Storage::put('restore-bd/restore.sql', $zip->getFromIndex(0));
            $zip->close();
        } catch (Throwable $th) {
            report($th);
            abort(500);
        }

        $db = mysqli_connect("$dbHost", "$dbUser", "$dbPass");

        //comprobar que existe la bd
        exec("mysql -u root -D $dbName", $output, $result);

        //comprobar si existe la base de datos para crearla
        if ($result == 1) {
            $crearDb = "CREATE DATABASE $dbName";
            $sql = mysqli_query($db, $crearDb);
        }

        $fileSqlRestore = Storage::path('restore-bd/restore.sql');

        $restaurarDb = exec("mysql -u $dbUser  $dbName < $fileSqlRestore ", $output, $result);

        return response()->json([], $result == 0 ? 200 : 500);
    }
}
