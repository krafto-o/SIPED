# Esquema de Base de Datos Maestro - SIPED

**Regla General:** Todas las tablas usan `id_nombretabla` como llave primaria (PK) autoincremental. Los nombres de tablas y columnas utilizan `snake_case`.

## 1. Módulo de Seguridad y Configuración

`usuarios`
- `id_usuario` (INT, PK)
- `nombre` (VARCHAR 45) NOT NULL
- `apellidos` (VARCHAR 45) NOT NULL
- `correo` (VARCHAR 100) UNIQUE NOT NULL
- `telefono` (VARCHAR 15) UNIQUE -- Permite login dual
- `password` (VARCHAR 255) NOT NULL -- Hash Bcrypt
- `rol` (ENUM: 'pediatra', 'recepcionista') NOT NULL
- `estado` (ENUM: 'activo', 'inactivo') DEFAULT 'activo'

`configuracion` -- (Variables globales editables)
- `clave` (VARCHAR 50, PK) -- Ej. 'duracion_primera_cita'
- `valor` (VARCHAR 100) NOT NULL
- `descripcion` (VARCHAR 200)

## 2. Módulo de Pacientes y Contacto

`tutor`
- `id_tutor` (INT, PK)
- `nombre`, `apellidos` (VARCHAR 45) NOT NULL
- `telefono` (VARCHAR 15) NOT NULL
- `correo` (VARCHAR 100)
- `direccion` (VARCHAR 150)
- `estado` (ENUM: 'activo', 'inactivo') DEFAULT 'activo'

`paciente`
- `id_paciente` (INT, PK)
- `nombre`, `apellidos` (VARCHAR 45) NOT NULL
- `fecha_nacimiento` (DATE) NOT NULL
- `sexo` (VARCHAR 15) NOT NULL
- `tipo_sangre` (VARCHAR 10)
- `alergias` (TEXT)
- `estado` (ENUM: 'activo', 'inactivo') DEFAULT 'activo'

`paciente_tutor` -- (Tabla Pivote: Relación Muchos a Muchos)
- `id_paciente` (INT, PK / FK -> paciente)
- `id_tutor` (INT, PK / FK -> tutor)
- `parentesco` (VARCHAR 45) NOT NULL

## 3. Módulo de Agenda y Citas

`citas`
- `id_cita` (INT, PK)
- `id_paciente` (INT, FK -> paciente) NOT NULL
- `id_usuario` (INT, FK -> usuarios) NOT NULL -- Médico
- `fecha_hora` (DATETIME) NOT NULL
- `tipo_cita` (ENUM: 'primera_vez', 'consecuente') DEFAULT 'consecuente'
- `motivo` (VARCHAR 100)
- `estado` (ENUM: 'pendiente', 'confirmada', 'cancelada', 'realizada') DEFAULT 'pendiente'

## 4. Módulo de Operación Médica (Consultas)

`borradores_consultas` -- (Autoguardado temporal)
- `id_cita` (INT, PK / FK -> citas)
- `id_usuario` (INT, FK -> usuarios)
- `datos_json` (TEXT) NOT NULL

`consultas` *(Se genera al finalizar la cita)*
- `id_consulta` (INT, PK)
- `id_cita` (INT, FK -> citas) NOT NULL
- `motivo_consulta` (VARCHAR 200)
- `anamnesis` (TEXT)
- `peso` (DECIMAL 5,2)
- `talla` (DECIMAL 5,2)
- `perimetro_cefalico` (DECIMAL 5,2)
- `temperatura` (DECIMAL 4,2)
- `frec_cardiaca` (INT)
- `frec_respiratoria` (INT)
- `notas_laboratorio` (TEXT)
- `diagnostico` (TEXT) NOT NULL
- `estado_pago` (ENUM: 'pendiente', 'pagado') DEFAULT 'pendiente'

`tratamientos` -- (Medicamentos recetados por consulta)
- `id_tratamiento` (INT, PK)
- `id_consulta` (INT, FK -> consultas) ON DELETE CASCADE
- `medicamento` (VARCHAR 100) NOT NULL
- `presentacion` (VARCHAR 100) NOT NULL
- `dosis_frecuencia` (VARCHAR 200) NOT NULL
- `funcion` (VARCHAR 150)

`archivos_adjuntos` -- (Estudios de Lab físicos)
- `id_archivo` (INT, PK)
- `id_consulta` (INT, FK -> consultas) ON DELETE CASCADE
- `nombre_original` (VARCHAR 150) NOT NULL
- `ruta_segura` (VARCHAR 255) NOT NULL -- Fuera del public HTML
- `fecha_subida` (DATETIME) DEFAULT CURRENT_TIMESTAMP

## 5. Módulo de Especialización (Vacunas)

`vacunas_catalogo`
- `id_vacuna` (INT, PK)
- `nombre` (VARCHAR 100) NOT NULL
- `esquema_edad` (VARCHAR 50) -- Ej. '2 meses', '1 año'

`vacunas_aplicadas`
- `id_registro` (INT, PK)
- `id_paciente` (INT, FK -> paciente) NOT NULL
- `id_vacuna` (INT, FK -> vacunas_catalogo) NOT NULL
- `fecha_aplicacion` (DATE) NOT NULL
- `aplicada_externamente` (BOOLEAN) DEFAULT FALSE
- `lote` (VARCHAR 50) NULL -- Puede ser Nulo si es externa

## 6. Módulo Administrativo (Pagos)

`pagos`
- `id_pago` (INT, PK)
- `id_consulta` (INT, FK -> consultas) NOT NULL
- `monto` (DECIMAL 10,2) NOT NULL
- `forma_pago` (ENUM: 'efectivo', 'tarjeta', 'transferencia') NOT NULL
- `fecha_pago` (DATETIME) DEFAULT CURRENT_TIMESTAMP
