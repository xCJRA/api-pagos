# API Pasarela de Pagos Unificada 💳

API REST que abstrae **Stripe** y **MercadoPago** detrás de una interfaz unificada.
El cliente llama a un solo endpoint y la API decide internamente qué pasarela usar.
Construida como proyecto de portafolio basado en experiencia real integrando pasarelas de pago en producción.

## 🚀 Tecnologías

- **PHP 8.2+** / **Laravel 11**
- **MySQL** — base de datos relacional
- **Laravel Sanctum** — autenticación por tokens
- **Stripe SDK** — pasarela de pago internacional
- **MercadoPago SDK** — pasarela de pago LATAM
- **L5-Swagger / OpenAPI 3.0** — documentación interactiva

## 🏗️ Arquitectura

El proyecto implementa el **Patrón Strategy** para desacoplar la lógica de cada pasarela:

```
POST /api/pagos
      │
      ▼
 PaymentService
      │
      ├──► StripeGateway      implements PaymentGatewayInterface
      └──► MercadoPagoGateway implements PaymentGatewayInterface
```

Esto permite agregar nuevas pasarelas (ej: PayPal, Conekta) sin modificar el Controller ni el Service existente.

## 📋 Endpoints

| Método | Endpoint | Descripción | Auth |
|--------|----------|-------------|------|
| POST | `/api/pagos` | Procesar un cobro | ✅ |
| GET | `/api/pagos/{uuid}` | Consultar estado | ✅ |
| POST | `/api/pagos/{uuid}/reembolso` | Reembolso parcial o total | ✅ |
| POST | `/api/webhooks/stripe` | Notificaciones de Stripe | ❌ |
| POST | `/api/webhooks/mercadopago` | Notificaciones de MercadoPago | ❌ |

Documentación interactiva disponible en `/api/docs` (Swagger UI).

## ⚙️ Instalación

```bash
# 1. Clonar el repositorio
git clone https://github.com/xCJRA/api-pagos.git
cd api-pagos

# 2. Instalar dependencias
composer install

# 3. Configurar entorno
cp .env.example .env
php artisan key:generate

# 4. Configurar base de datos en .env y ejecutar migraciones
php artisan migrate

# 5. Generar documentación Swagger
php artisan l5-swagger:generate

# 6. Levantar el servidor
php artisan serve
```

## 🔑 Variables de entorno

```env
DB_DATABASE=api_pagos
DB_USERNAME=root
DB_PASSWORD=

# Stripe (sandbox)
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...

# MercadoPago (sandbox)
MP_ACCESS_TOKEN=TEST-...
MP_PUBLIC_KEY=TEST-...
```

## 🧪 Pruebas en sandbox

**Generar token de acceso:**
```bash
php artisan tinker
# Dentro de Tinker:
$user = \App\Models\User::first();
echo $user->createToken('api-token')->plainTextToken;
```

**Cobro con Stripe (token de prueba `tok_visa`):**
```json
POST /api/pagos
Authorization: Bearer {token}

{
    "monto": 500.00,
    "moneda": "MXN",
    "gateway": "stripe",
    "descripcion": "Suscripción mensual",
    "token_tarjeta": "tok_visa"
}
```

**Tokens de prueba de Stripe:**

| Token | Resultado |
|-------|-----------|
| `tok_visa` | Pago aprobado |
| `tok_chargeDeclined` | Tarjeta rechazada |
| `tok_visa_debit` | Débito aprobado |

**Cobro con MercadoPago (sandbox):**
```json
POST /api/pagos
Authorization: Bearer {token}

{
    "monto": 500.00,
    "moneda": "MXN",
    "gateway": "mercadopago",
    "descripcion": "Suscripción mensual",
    "token_tarjeta": "TOKEN_GENERADO_POR_MP_JS",
    "email_pagador": "test_user@test.com",
    "payment_method_id": "visa",
    "cuotas": 1
}
```

**Reembolso total:**
```json
POST /api/pagos/{uuid}/reembolso
Authorization: Bearer {token}

{}
```

**Reembolso parcial:**
```json
POST /api/pagos/{uuid}/reembolso
Authorization: Bearer {token}

{
    "monto": 100.00
}
```

## 📁 Estructura relevante

```
app/
├── Contracts/
│   └── PaymentGatewayInterface.php   # Contrato que ambas pasarelas implementan
├── Services/
│   ├── PaymentService.php            # Resuelve qué gateway usar (Patrón Strategy)
│   ├── StripeGateway.php             # Implementación Stripe
│   └── MercadoPagoGateway.php        # Implementación MercadoPago
├── Http/
│   ├── Controllers/
│   │   ├── PagoController.php        # Cobros, consultas y reembolsos
│   │   └── WebhookController.php     # Notificaciones de las pasarelas
│   └── Requests/
│       ├── StorePagoRequest.php      # Validación del cobro
│       └── StoreReembolsoRequest.php # Validación del reembolso
└── Models/
    ├── Pago.php                      # Registro principal de cada transacción
    └── TransaccionLog.php            # Auditoría de eventos por pago
```

## 🗄️ Base de datos

**Tabla `pagos`**

| Campo | Tipo | Descripción |
|-------|------|-------------|
| uuid | string | Folio único expuesto al cliente |
| monto | decimal | Importe del cobro |
| moneda | string | Código ISO 4217 (MXN, USD) |
| gateway | enum | stripe \| mercadopago |
| estado | enum | pendiente \| completado \| fallido \| reembolsado |
| referencia_externa | string | ID del cobro en la pasarela |
| metadata | json | Respuesta completa de la pasarela |

**Tabla `transacciones_log`**

| Campo | Tipo | Descripción |
|-------|------|-------------|
| pago_id | foreignId | Relación con `pagos` |
| accion | string | charge \| refund \| webhook_received |
| resultado | enum | exito \| error |
| mensaje | text | Descripción del resultado |
| payload | json | Datos enviados a la pasarela |
| respuesta | json | Respuesta recibida de la pasarela |

## 👨‍💻 Autor

**César José Reyes Alonso** — Backend Developer  
📧 cesarjreyesa1@gmail.com  
[GitHub](https://github.com/xCJRA)
