import { Injectable, UnauthorizedException, Inject } from '@nestjs/common';
import { JwtService } from '@nestjs/jwt';
import { Pool } from 'pg';
import * as bcrypt from 'bcryptjs';
import { PG_POOL } from '../db/database.module';

export interface DbUser {
  id: string;
  tenant_id: string;
  email: string;
  name: string;
  password_hash: string;
  role: string;
  created_at: Date;
}

export interface JwtPayload {
  sub: string;
  email: string;
  tenantId: string;
  role: string;
}

@Injectable()
export class AuthService {
  constructor(
    @Inject(PG_POOL) private readonly pool: Pool,
    private readonly jwtService: JwtService,
  ) {}

  async login(email: string, password: string): Promise<{ accessToken: string }> {
    const result = await this.pool.query<DbUser>(
      `SELECT * FROM users WHERE email = $1 LIMIT 1`,
      [email],
    );

    const user = result.rows[0];
    if (!user) {
      throw new UnauthorizedException('Invalid credentials');
    }

    const valid = await bcrypt.compare(password, user.password_hash);
    if (!valid) {
      throw new UnauthorizedException('Invalid credentials');
    }

    const payload: JwtPayload = {
      sub: user.id,
      email: user.email,
      tenantId: user.tenant_id,
      role: user.role,
    };

    return { accessToken: this.jwtService.sign(payload) };
  }

  async getUserById(id: string, tenantId: string): Promise<DbUser | null> {
    const client = await this.pool.connect();
    try {
      await client.query(`SET LOCAL app.tenant_id = '${tenantId}'`);
      const result = await client.query<DbUser>(
        `SELECT id, tenant_id, email, name, role, created_at FROM users WHERE id = $1`,
        [id],
      );
      return result.rows[0] ?? null;
    } finally {
      client.release();
    }
  }
}
