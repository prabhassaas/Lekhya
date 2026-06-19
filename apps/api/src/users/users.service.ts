import { Injectable, Inject } from '@nestjs/common';
import { Pool } from 'pg';
import { PG_POOL } from '../db/database.module';

@Injectable()
export class UsersService {
  constructor(@Inject(PG_POOL) private readonly pool: Pool) {}

  async findByEmail(email: string, tenantId: string) {
    const client = await this.pool.connect();
    try {
      await client.query(`SET LOCAL app.tenant_id = '${tenantId}'`);
      const result = await client.query(
        `SELECT id, tenant_id, email, name, role, created_at FROM users WHERE email = $1`,
        [email],
      );
      return result.rows[0] ?? null;
    } finally {
      client.release();
    }
  }
}
