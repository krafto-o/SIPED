---
designTokens:
  colors:
    primary: "#0ea5e9"
    secondary: "#10b981"
    danger: "#ef4444"
    warning: "#f59e0b"
    background: "#f8fafc"
    surface: "#ffffff"
    textPrimary: "#1e293b"
    textMuted: "#64748b"
    border: "#e2e8f0"
  typography:
    body: "system-ui, -apple-system, sans-serif"
  spacing:
    xs: "4px"
    sm: "8px"
    md: "16px"
    lg: "24px"
    xl: "32px"
  components:
    card:
      backgroundColor: surface
      rounded: "8px"
      padding: lg
    button:
      rounded: "8px"
      padding: md
---
# Sistema de Diseño SIPED

## Overview

SIPED es un Sistema Integral de Gestión para Consultorio Pediátrico. La interfaz debe transmitir limpieza médica, confianza y utilidad técnica. No debe haber adornos innecesarios; es una herramienta de trabajo rápido para pediatras y recepcionistas.

## Colors

La paleta se basa en neutrales de alto contraste con colores semánticos para la interacción médica:

- **Primary:** Azul cielo oscuro, usado para botones principales y enlaces. Transmite confianza clínica.
- **Secondary:** Verde esmeralda, usado para estados de éxito (ej. citas realizadas, pagos procesados).
- **Danger:** Rojo puro para errores, cancelación de citas o borrados lógicos.
- **Warning:** Naranja/Amarillo usado exclusivamente para alertar sobre vacunas atrasadas en el perfil del paciente.
- **Background y Surface:** Todo el sistema usa un fondo general ligeramente gris (`background`) para reducir la fatiga visual durante turnos largos, mientras que la información principal reside en tarjetas de color blanco puro (`surface`).

## Typography

El sistema usa tipografía nativa de la plataforma para garantizar la máxima velocidad de carga, sin dependencias externas. Se prioriza la legibilidad estricta de datos médicos (pesos, tallas, edades, fórmulas de medicamentos) por encima de la estética.

## Spacing and Layout

El espaciado es estrictamente matemático y utilitario.

- Los márgenes y rellenos deben consumir los tokens de `spacing` para mantener consistencia visual.
- Las vistas complejas (como la pantalla de Consultas con el historial médico y recetas) deben utilizar CSS Flexbox y Grid para aprovechar el espacio en pantalla sin requerir desplazamiento excesivo.
