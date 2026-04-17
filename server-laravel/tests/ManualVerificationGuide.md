# Guía de Verificación Manual (Caja Negra)

Esta guía complementa las pruebas automatizadas para asegurar que la interfaz visual y el servidor externo de GLPI se comportan como se espera desdel el punto de vista del usuario.

## 1. Verificación de Dashboard de Libros
- **Objetivo**: Confirmar que los cambios en Laravel se reflejan visualmente en Vue.js.
- **Pasos**:
  1. Entrar al sistema como `bibliotecario`.
  2. Crear un nuevo libro.
  3. Verificar que aparece inmediatamente en la tabla sin necesidad de recargar manualmente (o tras recargar).
  4. Editar el autor y confirmar que la etiqueta cambia en la vista.

## 2. Verificación en GLPI (Asset)
- **Objetivo**: Confirmar sincronización física con el inventario.
- **Pasos**:
  1. Tras crear un libro en la Web, anotar su `glpi_id` (si está visible en consola o DB).
  2. Entrar a `http://localhost:8080` (GLPI).
  3. Ir a **Activos > Libros** (Plugin GenericObject).
  4. Buscar el libro por título o ISBN.
  5. Validar que los campos "Género", "Editorial" y "Estado" coincidan con lo seleccionado en la Web.

## 3. Verificación de Reporte de Daños (Tickets)
- **Objetivo**: Validar el flujo de soporte.
- **Pasos**:
  1. En la Web, seleccionar un libro y presionar "Reportar Incidencia".
  2. Llenar el formulario y enviar.
  3. En GLPI, ir a **Soporte > Tickets**.
  4. Confirmar que el ticket:
     - Está asignado al técnico `soporte_biblioteca`.
     - Tiene al bibliotecario actual como `Solicitante`.
     - Tiene el libro vinculado en la pestaña de "Elementos".

## 4. Verificación de Roles (RBAC)
- **Objetivo**: Validar restricciones de seguridad visuales.
- **Pasos**:
  1. Entrar como un usuario con rol `bibliotecario`.
  2. Intentar buscar el botón de "Eliminar" libro. No debería estar presente o debería estar deshabilitado.
  3. Intentar acceder a la ruta de borrado vía consola (opcional) para confirmar el error 403.
