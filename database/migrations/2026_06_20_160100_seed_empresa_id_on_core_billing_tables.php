<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Backfills empresa_id on the core billing tables using the existing
     * relationships: repases/agendas inherit from clinicas.empresa_id,
     * gastos/repase_examenes inherit from their parent repase.empresa_id.
     *
     * Idempotent: only updates rows where empresa_id IS NULL.
     *
     * MySQL uses UPDATE ... JOIN (its native, most efficient form). Other
     * drivers fall back to correlated subqueries (standard SQL). The driver
     * branch is the same pattern Laravel's own migrations use — MySQL keeps
     * its idiomatic syntax, no production code is shaped by the test driver.
     */
    public function up(): void
    {
        $isMysql = DB::getDriverName() === 'mysql';

        // repases <- clinicas.empresa_id
        if (Schema::hasColumn('repases', 'empresa_id')) {
            DB::statement($isMysql
                ? 'UPDATE repases r JOIN clinicas c ON r.clinica_id = c.id
                   SET r.empresa_id = c.empresa_id
                   WHERE r.empresa_id IS NULL AND c.empresa_id IS NOT NULL'
                : 'UPDATE repases
                   SET empresa_id = (SELECT c.empresa_id FROM clinicas c WHERE c.id = repases.clinica_id)
                   WHERE empresa_id IS NULL
                     AND clinica_id IN (SELECT id FROM clinicas WHERE empresa_id IS NOT NULL)'
            );
        }

        // agendas <- clinicas.empresa_id
        if (Schema::hasColumn('agendas', 'empresa_id')) {
            DB::statement($isMysql
                ? 'UPDATE agendas a JOIN clinicas c ON a.clinica_id = c.id
                   SET a.empresa_id = c.empresa_id
                   WHERE a.empresa_id IS NULL AND c.empresa_id IS NOT NULL'
                : 'UPDATE agendas
                   SET empresa_id = (SELECT c.empresa_id FROM clinicas c WHERE c.id = agendas.clinica_id)
                   WHERE empresa_id IS NULL
                     AND clinica_id IN (SELECT id FROM clinicas WHERE empresa_id IS NOT NULL)'
            );
        }

        // gastos <- repases.empresa_id
        if (Schema::hasColumn('gastos', 'empresa_id')) {
            DB::statement($isMysql
                ? 'UPDATE gastos g JOIN repases r ON g.repase_id = r.id
                   SET g.empresa_id = r.empresa_id
                   WHERE g.empresa_id IS NULL AND r.empresa_id IS NOT NULL'
                : 'UPDATE gastos
                   SET empresa_id = (SELECT r.empresa_id FROM repases r WHERE r.id = gastos.repase_id)
                   WHERE empresa_id IS NULL
                     AND repase_id IN (SELECT id FROM repases WHERE empresa_id IS NOT NULL)'
            );
        }

        // repase_examenes <- repases.empresa_id
        if (Schema::hasColumn('repase_examenes', 'empresa_id')) {
            DB::statement($isMysql
                ? 'UPDATE repase_examenes re JOIN repases r ON re.repase_id = r.id
                   SET re.empresa_id = r.empresa_id
                   WHERE re.empresa_id IS NULL AND r.empresa_id IS NOT NULL'
                : 'UPDATE repase_examenes
                   SET empresa_id = (SELECT r.empresa_id FROM repases r WHERE r.id = repase_examenes.repase_id)
                   WHERE empresa_id IS NULL
                     AND repase_id IN (SELECT id FROM repases WHERE empresa_id IS NOT NULL)'
            );
        }
    }

    public function down(): void
    {
        // No-op: a backfill has no meaningful inverse beyond the column drop
        // handled by the add_empresa_id migration's down().
    }
};
