# Seeders del Sistema de Contabilidad

Este directorio contiene los seeders para poblar la base de datos con datos iniciales y de prueba.

## Seeders Disponibles

### 1. ExamenSeeder
Crea los 7 exámenes médicos predefinidos del sistema con precios base.

**Uso:**
```bash
php artisan db:seed --class=ExamenSeeder
```

### 2. CurrentExamPricesSeeder
Actualiza los precios de los exámenes con los valores actuales del sistema.

**Precios actuales:**
- Electroencefalograma: R$100.00 (sin nota) / R$120.00 (con nota)
- Electroencefalograma c/ mapa: R$120.00 / R$140.00
- Electroencefalograma c/ mapeamento 3d + foto estimulo: R$200.00 / R$220.00
- Electroneuromiografia MEMBROS unilateral: R$150.00 / R$180.00
- Electroneuromiografia FACIAL unilateral: R$170.00 / R$200.00
- Potencial evocado VISUAL unilateral: R$146.00 / R$166.00
- Potencial evocado AUDITIVO unilateral: R$146.00 / R$166.00

**Uso:**
```bash
php artisan db:seed --class=CurrentExamPricesSeeder
```

### 3. AdminUserSeeder
Crea el usuario administrador del sistema.

**Credenciales:**
- Email: admin@example.com
- Password: password

**Uso:**
```bash
php artisan db:seed --class=AdminUserSeeder
```

### 4. ClinicaSeeder
Crea clínicas de ejemplo para el sistema.

**Uso:**
```bash
php artisan db:seed --class=ClinicaSeeder
```

### 5. RepaseSeeder
Crea repases médicos de ejemplo con exámenes y gastos asociados.

**Uso:**
```bash
php artisan db:seed --class=RepaseSeeder
```

### 6. TestDataSeeder
Crea datos de prueba completos para desarrollo y testing.

**Uso:**
```bash
php artisan db:seed --class=TestDataSeeder
```

## Ejecutar Todos los Seeders

Para ejecutar todos los seeders en el orden correcto:

```bash
php artisan db:seed
```

O para refrescar la base de datos y ejecutar todos los seeders:

```bash
php artisan migrate:fresh --seed
```

## Orden de Ejecución

El orden de ejecución de los seeders es importante para mantener la integridad referencial:

1. **ExamenSeeder** - Crea los exámenes base
2. **CurrentExamPricesSeeder** - Actualiza los precios actuales
3. **AdminUserSeeder** - Crea el usuario administrador
4. **ClinicaSeeder** - Crea las clínicas
5. **RepaseSeeder** - Crea los repases (requiere exámenes y clínicas)

## Actualizar Precios

Si los precios de los exámenes cambian en el sistema, actualiza el archivo `CurrentExamPricesSeeder.php` con los nuevos valores y ejecuta:

```bash
php artisan db:seed --class=CurrentExamPricesSeeder
```

Este comando actualizará los precios sin afectar otros datos.
