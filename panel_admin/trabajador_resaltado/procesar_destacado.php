<?php
include '../../db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'administrador') {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$accion = $_POST['accion'] ?? '';

try {
    switch ($accion) {
        case 'crear':
            $id_usuario = $_POST['id_usuario'] ?? '';
            $mensaje_merito = trim($_POST['mensaje_merito'] ?? '');
            $fecha_inicio = $_POST['fecha_inicio'] ?? '';
            $fecha_fin = $_POST['fecha_fin'] ?? null;
            $mostrar_popup = isset($_POST['mostrar_popup']) ? 1 : 0;
            
            // Validaciones
            if (empty($id_usuario) || empty($mensaje_merito) || empty($fecha_inicio)) {
                throw new Exception('Todos los campos obligatorios deben ser completados');
            }
            
            if (strlen($mensaje_merito) < 10) {
                throw new Exception('El mensaje de mérito debe tener al menos 10 caracteres');
            }
            
            // Verificar que el usuario existe y es un trabajador verificado (rol = usuario)
            $stmt_user = $pdo->prepare("
                SELECT id, nombre_completo, rol 
                FROM usuarios 
                WHERE id = ? AND verificado = 1 AND rol = 'usuario'
            ");
            $stmt_user->execute([$id_usuario]);
            $usuario = $stmt_user->fetch(PDO::FETCH_ASSOC);
            
            if (!$usuario) {
                throw new Exception('El usuario seleccionado no es válido, no está verificado o no es un trabajador');
            }
            
            // Verificar fechas
            if ($fecha_fin && $fecha_fin < $fecha_inicio) {
                throw new Exception('La fecha de fin no puede ser anterior a la fecha de inicio');
            }
            
            // Verificar que no hay otro trabajador destacado activo en las mismas fechas
            $fecha_fin_check = $fecha_fin ?: '2099-12-31';
            $query_conflicto = "
                SELECT COUNT(*) as conflictos 
                FROM trabajadores_destacados 
                WHERE (fecha_fin IS NULL OR fecha_fin >= ?) 
                AND fecha_inicio <= ?
                AND id_usuario != ?
            ";
            $stmt_conflicto = $pdo->prepare($query_conflicto);
            $stmt_conflicto->execute([$fecha_inicio, $fecha_fin_check, $id_usuario]);
            $conflictos = $stmt_conflicto->fetchColumn();
            
            if ($conflictos > 0) {
                throw new Exception('Ya existe otro trabajador destacado activo en ese período de tiempo');
            }
            
            // Insertar nuevo trabajador destacado
            $stmt = $pdo->prepare("
                INSERT INTO trabajadores_destacados 
                (id_usuario, mensaje_merito, fecha_inicio, fecha_fin, mostrar_popup, creado_por) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $id_usuario,
                $mensaje_merito,
                $fecha_inicio,
                $fecha_fin ?: null,
                $mostrar_popup,
                $_SESSION['user_id']
            ]);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Trabajador destacado creado exitosamente',
                'id' => $pdo->lastInsertId()
            ]);
            break;
            
        case 'editar':
            $id = $_POST['id'] ?? '';
            $id_usuario = $_POST['id_usuario'] ?? '';
            $mensaje_merito = trim($_POST['mensaje_merito'] ?? '');
            $fecha_inicio = $_POST['fecha_inicio'] ?? '';
            $fecha_fin = $_POST['fecha_fin'] ?? null;
            $mostrar_popup = isset($_POST['mostrar_popup']) ? 1 : 0;
            
            // Validaciones
            if (empty($id) || empty($id_usuario) || empty($mensaje_merito) || empty($fecha_inicio)) {
                throw new Exception('Todos los campos obligatorios deben ser completados');
            }
            
            if (strlen($mensaje_merito) < 10) {
                throw new Exception('El mensaje de mérito debe tener al menos 10 caracteres');
            }
            
            // Verificar que el registro existe
            $stmt_exists = $pdo->prepare("SELECT id FROM trabajadores_destacados WHERE id = ?");
            $stmt_exists->execute([$id]);
            if (!$stmt_exists->fetch()) {
                throw new Exception('El registro no existe');
            }
            
            // Verificar que el usuario existe y es un trabajador verificado
            $stmt_user = $pdo->prepare("
                SELECT id, nombre_completo, rol 
                FROM usuarios 
                WHERE id = ? AND verificado = 1 AND rol = 'usuario'
            ");
            $stmt_user->execute([$id_usuario]);
            $usuario = $stmt_user->fetch(PDO::FETCH_ASSOC);
            
            if (!$usuario) {
                throw new Exception('El usuario seleccionado no es válido, no está verificado o no es un trabajador');
            }
            
            // Verificar fechas
            if ($fecha_fin && $fecha_fin < $fecha_inicio) {
                throw new Exception('La fecha de fin no puede ser anterior a la fecha de inicio');
            }
            
            // Verificar conflictos (excluyendo el registro actual)
            $fecha_fin_check = $fecha_fin ?: '2099-12-31';
            $query_conflicto = "
                SELECT COUNT(*) as conflictos 
                FROM trabajadores_destacados 
                WHERE id != ?
                AND (fecha_fin IS NULL OR fecha_fin >= ?) 
                AND fecha_inicio <= ?
                AND id_usuario != ?
            ";
            $stmt_conflicto = $pdo->prepare($query_conflicto);
            $stmt_conflicto->execute([$id, $fecha_inicio, $fecha_fin_check, $id_usuario]);
            $conflictos = $stmt_conflicto->fetchColumn();
            
            if ($conflictos > 0) {
                throw new Exception('Ya existe otro trabajador destacado activo en ese período de tiempo');
            }
            
            // Actualizar registro
            $stmt = $pdo->prepare("
                UPDATE trabajadores_destacados 
                SET id_usuario = ?, mensaje_merito = ?, fecha_inicio = ?, fecha_fin = ?, mostrar_popup = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $id_usuario,
                $mensaje_merito,
                $fecha_inicio,
                $fecha_fin ?: null,
                $mostrar_popup,
                $id
            ]);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Trabajador destacado actualizado exitosamente'
            ]);
            break;
            
        case 'finalizar':
            $id = $_POST['id'] ?? '';
            
            if (empty($id)) {
                throw new Exception('ID requerido');
            }
            
            // Verificar que el registro existe
            $stmt_check = $pdo->prepare("
                SELECT id, fecha_fin 
                FROM trabajadores_destacados 
                WHERE id = ?
            ");
            $stmt_check->execute([$id]);
            $registro = $stmt_check->fetch(PDO::FETCH_ASSOC);
            
            if (!$registro) {
                throw new Exception('El registro no existe');
            }
            
            // Finalizar el período (establecer fecha_fin a hoy)
            $stmt = $pdo->prepare("
                UPDATE trabajadores_destacados 
                SET fecha_fin = CURDATE(), mostrar_popup = 0
                WHERE id = ?
            ");
            
            $stmt->execute([$id]);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Período de trabajador destacado finalizado'
            ]);
            break;
            
        case 'reactivar':
            $id = $_POST['id'] ?? '';
            
            if (empty($id)) {
                throw new Exception('ID requerido');
            }
            
            // Verificar que el registro existe
            $stmt_check = $pdo->prepare("
                SELECT td.*, u.nombre_completo 
                FROM trabajadores_destacados td
                INNER JOIN usuarios u ON td.id_usuario = u.id
                WHERE td.id = ?
            ");
            $stmt_check->execute([$id]);
            $registro = $stmt_check->fetch(PDO::FETCH_ASSOC);
            
            if (!$registro) {
                throw new Exception('El registro no existe');
            }
            
            // Verificar que no hay conflictos con otros destacados activos
            $query_conflicto = "
                SELECT COUNT(*) as conflictos 
                FROM trabajadores_destacados 
                WHERE id != ?
                AND (fecha_fin IS NULL OR fecha_fin >= CURDATE()) 
                AND fecha_inicio <= CURDATE()
            ";
            $stmt_conflicto = $pdo->prepare($query_conflicto);
            $stmt_conflicto->execute([$id]);
            $conflictos = $stmt_conflicto->fetchColumn();
            
            if ($conflictos > 0) {
                throw new Exception('No se puede reactivar porque ya existe otro trabajador destacado activo');
            }
            
            // Reactivar
            $stmt = $pdo->prepare("
                UPDATE trabajadores_destacados 
                SET mostrar_popup = 1, fecha_fin = NULL
                WHERE id = ?
            ");
            
            $stmt->execute([$id]);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Trabajador destacado reactivado exitosamente'
            ]);
            break;
            
        case 'eliminar':
            $id = $_POST['id'] ?? '';
            
            if (empty($id)) {
                throw new Exception('ID requerido');
            }
            
            // Verificar que el registro existe
            $stmt_check = $pdo->prepare("
                SELECT td.*, u.nombre_completo 
                FROM trabajadores_destacados td
                INNER JOIN usuarios u ON td.id_usuario = u.id
                WHERE td.id = ?
            ");
            $stmt_check->execute([$id]);
            $registro = $stmt_check->fetch(PDO::FETCH_ASSOC);
            
            if (!$registro) {
                throw new Exception('El registro no existe');
            }
            
            // Eliminar registro
            $stmt = $pdo->prepare("DELETE FROM trabajadores_destacados WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Registro de trabajador destacado eliminado permanentemente'
            ]);
            break;
            
        case 'toggle_status':
            $id = $_POST['id'] ?? '';
            $activo = $_POST['activo'] ?? '';
            
            if (empty($id) || $activo === '') {
                throw new Exception('Parámetros requeridos');
            }
            
            $nuevo_estado = $activo ? 1 : 0;
            
            // Si se está activando, verificar conflictos
            if ($nuevo_estado == 1) {
                $query_conflicto = "
                    SELECT COUNT(*) as conflictos 
                    FROM trabajadores_destacados 
                    WHERE id != ?
                    AND (fecha_fin IS NULL OR fecha_fin >= CURDATE()) 
                    AND fecha_inicio <= CURDATE()
                ";
                $stmt_conflicto = $pdo->prepare($query_conflicto);
                $stmt_conflicto->execute([$id]);
                $conflictos = $stmt_conflicto->fetchColumn();
                
                if ($conflictos > 0) {
                    throw new Exception('No se puede activar porque ya existe otro trabajador destacado activo');
                }
            }
            
            // Cambiar estado mediante fecha_fin
            if ($nuevo_estado == 0) {
                // Desactivar: poner fecha_fin = hoy
                $stmt = $pdo->prepare("
                    UPDATE trabajadores_destacados 
                    SET fecha_fin = CURDATE(), mostrar_popup = 0
                    WHERE id = ?
                ");
            } else {
                // Activar: quitar fecha_fin
                $stmt = $pdo->prepare("
                    UPDATE trabajadores_destacados 
                    SET fecha_fin = NULL, mostrar_popup = 1
                    WHERE id = ?
                ");
            }
            
            $stmt->execute([$id]);
            
            $mensaje = $nuevo_estado ? 'Trabajador destacado activado' : 'Trabajador destacado desactivado';
            
            echo json_encode([
                'success' => true, 
                'message' => $mensaje
            ]);
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    error_log("Error PDO en procesar_destacado.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Error en la base de datos. Por favor, inténtalo de nuevo.'
    ]);
}
?>