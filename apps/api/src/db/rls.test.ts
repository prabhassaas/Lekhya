import { describe, it, expect, beforeAll, afterAll } from 'vitest';
import { Pool, PoolClient } from 'pg';
import * as bcrypt from 'bcryptjs';

const DATABASE_URL =
  process.env.DATABASE_URL ?? 'postgresql://lekhya:lekhya@localhost:5432/lekhya';

let pool: Pool;
let tenantAId: string;
let tenantBId: string;

beforeAll(async () => {
  pool = new Pool({ connectionString: DATABASE_URL });

  const client = await pool.connect();
  try {
    // Create tenant A
    const resA = await client.query<{ id: string }>(
      `INSERT INTO tenants (name) VALUES ($1) RETURNING id`,
      ['RLS Test Tenant A'],
    );
    tenantAId = resA.rows[0].id;

    // Create tenant B
    const resB = await client.query<{ id: string }>(
      `INSERT INTO tenants (name) VALUES ($1) RETURNING id`,
      ['RLS Test Tenant B'],
    );
    tenantBId = resB.rows[0].id;

    const hash = await bcrypt.hash('test', 10);

    // Create user for tenant A
    await client.query(
      `INSERT INTO users (tenant_id, email, name, password_hash, role)
       VALUES ($1, $2, $3, $4, $5)`,
      [tenantAId, 'usera@rls.test', 'User A', hash, 'owner'],
    );

    // Create user for tenant B
    await client.query(
      `INSERT INTO users (tenant_id, email, name, password_hash, role)
       VALUES ($1, $2, $3, $4, $5)`,
      [tenantBId, 'userb@rls.test', 'User B', hash, 'owner'],
    );
  } finally {
    client.release();
  }
});

afterAll(async () => {
  // Clean up test data
  const client = await pool.connect();
  try {
    // Bypass RLS for cleanup by using superuser context
    await client.query(`DELETE FROM tenants WHERE name IN ($1, $2)`, [
      'RLS Test Tenant A',
      'RLS Test Tenant B',
    ]);
  } finally {
    client.release();
  }
  await pool.end();
});

describe('RLS: cross-tenant isolation', () => {
  it('returns only tenant A users when app.tenant_id is set to tenant A', async () => {
    const client: PoolClient = await pool.connect();
    try {
      // Set app.tenant_id to tenant A
      await client.query(`SET LOCAL app.tenant_id = '${tenantAId}'`);

      const result = await client.query<{ email: string; tenant_id: string }>(
        `SELECT email, tenant_id FROM users WHERE email IN ('usera@rls.test', 'userb@rls.test')`,
      );

      expect(result.rows).toHaveLength(1);
      expect(result.rows[0].email).toBe('usera@rls.test');
      expect(result.rows[0].tenant_id).toBe(tenantAId);
    } finally {
      client.release();
    }
  });

  it('returns only tenant B users when app.tenant_id is set to tenant B', async () => {
    const client: PoolClient = await pool.connect();
    try {
      await client.query(`SET LOCAL app.tenant_id = '${tenantBId}'`);

      const result = await client.query<{ email: string; tenant_id: string }>(
        `SELECT email, tenant_id FROM users WHERE email IN ('usera@rls.test', 'userb@rls.test')`,
      );

      expect(result.rows).toHaveLength(1);
      expect(result.rows[0].email).toBe('userb@rls.test');
      expect(result.rows[0].tenant_id).toBe(tenantBId);
    } finally {
      client.release();
    }
  });

  it('returns no users when app.tenant_id is not set', async () => {
    const client: PoolClient = await pool.connect();
    try {
      // Don't set app.tenant_id - RLS should block all rows
      const result = await client.query<{ email: string }>(
        `SELECT email FROM users WHERE email IN ('usera@rls.test', 'userb@rls.test')`,
      );

      expect(result.rows).toHaveLength(0);
    } finally {
      client.release();
    }
  });
});
