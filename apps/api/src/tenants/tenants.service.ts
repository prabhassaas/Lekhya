import { Injectable, Inject } from '@nestjs/common';
import { Pool } from 'pg';
import { PG_POOL } from '../db/database.module';

@Injectable()
export class TenantsService {
  constructor(@Inject(PG_POOL) private readonly pool: Pool) {}

  async findById(id: string) {
    const result = await this.pool.query<{ id: string; name: string; created_at: Date }>(
      `SELECT id, name, created_at FROM tenants WHERE id = $1`,
      [id],
    );
    return result.rows[0] ?? null;
  }
}
