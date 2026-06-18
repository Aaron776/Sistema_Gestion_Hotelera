CREATE TABLE usuarios (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    cedula VARCHAR(20) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password TEXT NOT NULL,
    telefono VARCHAR(20),
    direccion TEXT,
    rol VARCHAR(20) CHECK (rol IN ('admin','recepcionista','cliente')) NOT NULL,
    estado VARCHAR(10) CHECK (estado IN ('activo','inactivo')) DEFAULT 'activo',
    ultimo_acceso TIMESTAMP,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE configuracion_hotel (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    ruc VARCHAR(20) NOT NULL,
    direccion TEXT,
    telefono VARCHAR(20),
    email VARCHAR(100),
    logo_url TEXT,
    porcentaje_impuesto NUMERIC(5,2) DEFAULT 12.00,
    moneda VARCHAR(10) DEFAULT 'USD'
);

CREATE TABLE habitaciones (
    id SERIAL PRIMARY KEY,
    numero VARCHAR(10) UNIQUE NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    precio NUMERIC(10,2) NOT NULL,
    capacidad INT NOT NULL,
    estado VARCHAR(20) CHECK (estado IN ('disponible','ocupada','mantenimiento')) DEFAULT 'disponible'
);

CREATE TABLE mantenimientos_habitacion (
    id SERIAL PRIMARY KEY,
    habitacion_id INT REFERENCES habitaciones(id) ON DELETE CASCADE,
    motivo TEXT NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin_estimada DATE,
    estado VARCHAR(20) CHECK (estado IN ('en_proceso','finalizado')) DEFAULT 'en_proceso',
    registrado_por INT REFERENCES usuarios(id),
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE reservas (
    id SERIAL PRIMARY KEY,
    cliente_id INT REFERENCES usuarios(id), 
    empleado_id INT REFERENCES usuarios(id), -- Quien registró la reserva (puede ser nulo si el cliente reservó online él mismo)
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    estado VARCHAR(20) CHECK (estado IN ('pendiente','confirmada','cancelada','finalizada')) DEFAULT 'pendiente',
    total NUMERIC(10,2),
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE reserva_habitacion (
    id SERIAL PRIMARY KEY,
    reserva_id INT REFERENCES reservas(id) ON DELETE CASCADE,
    habitacion_id INT REFERENCES habitaciones(id),
    precio NUMERIC(10,2)
);

CREATE TABLE servicios (
    id SERIAL PRIMARY KEY,
    nombre VARCHAR(100),
    precio NUMERIC(10,2)
);

CREATE TABLE reserva_servicio (
    id SERIAL PRIMARY KEY,
    reserva_id INT REFERENCES reservas(id) ON DELETE CASCADE,
    servicio_id INT REFERENCES servicios(id),
    cantidad INT DEFAULT 1,
    subtotal NUMERIC(10,2)
);

CREATE TABLE pagos (
    id SERIAL PRIMARY KEY,
    reserva_id INT REFERENCES reservas(id) ON DELETE CASCADE,
    usuario_id INT REFERENCES usuarios(id), -- Quien registra el cobro (el recepcionista o admin)
    monto NUMERIC(10,2) NOT NULL,
    metodo VARCHAR(20) CHECK (metodo IN ('efectivo','tarjeta','transferencia')),
    estado VARCHAR(20) CHECK (estado IN ('pendiente','pagado')) DEFAULT 'pendiente',
    comprobante_referencia VARCHAR(100),
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE facturas (
    id SERIAL PRIMARY KEY,
    reserva_id INT REFERENCES reservas(id) ON DELETE CASCADE,
    numero_factura VARCHAR(50) UNIQUE NOT NULL,
    cliente_documento VARCHAR(20), -- Por si la factura sale a nombre de otra empresa
    cliente_nombre VARCHAR(100),
    cliente_direccion TEXT,
    subtotal NUMERIC(10,2) NOT NULL,
    impuestos NUMERIC(10,2) NOT NULL,
    total NUMERIC(10,2) NOT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE notificaciones (
    id SERIAL PRIMARY KEY,
    usuario_id INT REFERENCES usuarios(id) ON DELETE CASCADE,
    mensaje TEXT,
    leida BOOLEAN DEFAULT FALSE,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
