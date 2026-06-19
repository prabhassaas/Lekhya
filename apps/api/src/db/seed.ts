import { Pool } from 'pg';
import * as bcrypt from 'bcryptjs';

async function seed() {
  const pool = new Pool({
    connectionString: process.env.DATABASE_URL,
  });

  const client = await pool.connect();
  try {
    // Create a default tenant
    const tenantResult = await client.query<{ id: string }>(
      `INSERT INTO tenants (name)
       VALUES ($1)
       ON CONFLICT DO NOTHING
       RETURNING id`,
      ['Demo Company'],
    );

    let tenantId: string;
    if (tenantResult.rows.length === 0) {
      const existing = await client.query<{ id: string }>(
        `SELECT id FROM tenants WHERE name = $1`,
        ['Demo Company'],
      );
      tenantId = existing.rows[0].id;
    } else {
      tenantId = tenantResult.rows[0].id;
    }

    const passwordHash = await bcrypt.hash('password123', 10);

    await client.query(
      `INSERT INTO users (tenant_id, email, name, password_hash, role)
       VALUES ($1, $2, $3, $4, $5)
       ON CONFLICT (tenant_id, email) DO NOTHING`,
      [tenantId, 'owner@demo.com', 'Demo Owner', passwordHash, 'owner'],
    );

    console.log('Seed completed successfully');
    console.log('Tenant ID:', tenantId);
    console.log('Login: owner@demo.com / password123');
  } finally {
    client.release();
    await pool.end();
  }
}

seed().catch((err) => {
  console.error('Seed failed:', err);
  process.exit(1);
});
