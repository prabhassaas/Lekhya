import { describe, it, expect, vi, beforeEach } from 'vitest';
import { AuthService, DbUser } from './auth.service';
import { JwtService } from '@nestjs/jwt';
import * as bcrypt from 'bcryptjs';

const mockUser: DbUser = {
  id: 'user-uuid-1',
  tenant_id: 'tenant-uuid-1',
  email: 'owner@demo.com',
  name: 'Demo Owner',
  password_hash: bcrypt.hashSync('password123', 10),
  role: 'owner',
  created_at: new Date(),
};

const mockPool = {
  query: vi.fn(),
  connect: vi.fn(),
};

const mockJwtService = {
  sign: vi.fn().mockReturnValue('mock-jwt-token'),
} as unknown as JwtService;

describe('AuthService', () => {
  let service: AuthService;

  beforeEach(() => {
    vi.clearAllMocks();
    service = new AuthService(mockPool as never, mockJwtService);
  });

  it('returns an accessToken on valid credentials', async () => {
    mockPool.query.mockResolvedValueOnce({ rows: [mockUser] });

    const result = await service.login('owner@demo.com', 'password123');

    expect(result).toEqual({ accessToken: 'mock-jwt-token' });
    expect(mockJwtService.sign).toHaveBeenCalledWith({
      sub: mockUser.id,
      email: mockUser.email,
      tenantId: mockUser.tenant_id,
      role: mockUser.role,
    });
  });

  it('throws UnauthorizedException when user not found', async () => {
    mockPool.query.mockResolvedValueOnce({ rows: [] });

    await expect(service.login('nobody@demo.com', 'password123')).rejects.toThrow(
      'Invalid credentials',
    );
  });

  it('throws UnauthorizedException on wrong password', async () => {
    mockPool.query.mockResolvedValueOnce({ rows: [mockUser] });

    await expect(service.login('owner@demo.com', 'wrongpassword')).rejects.toThrow(
      'Invalid credentials',
    );
  });
});
