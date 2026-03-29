<?php

require_once 'Funciones_SQL.php';

function resetearBaseDeDatos($db)
{
    // 1. Desactivar revisión de llaves foráneas para poder borrar tablas en cualquier orden
    $db->exec("SET FOREIGN_KEY_CHECKS = 0;");
    // 2. Lista de tablas a eliminar
    $tablas = ['medico', 'tutor', 'recepcionista', 'paciente','citas','historial_clinico','diagnostico'];
    try {
        echo "Iniciando limpieza...<br>";
        foreach ($tablas as $tabla) {
            $db->exec("DROP TABLE IF EXISTS $tabla;");
            echo "✔ Tabla '$tabla' eliminada.<br>";
        }
    } catch (PDOException $e) {
        $db->exec("SET FOREIGN_KEY_CHECKS = 1;");
        registrarError("Error reseteando DB: " . $e->getMessage());
        echo "❌ Error fatal. Revisa el log.";
    }
}

//Conectar a la db
$db = conectar();
// Borrar las tablas
resetearBaseDeDatos($db);
