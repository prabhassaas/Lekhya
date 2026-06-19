import { z } from 'zod';
import {
  LoginRequestSchema,
  LoginResponseSchema,
  TenantSchema,
  UserSchema,
} from './schemas';

export type LoginRequest = z.infer<typeof LoginRequestSchema>;
export type LoginResponse = z.infer<typeof LoginResponseSchema>;
export type Tenant = z.infer<typeof TenantSchema>;
export type User = z.infer<typeof UserSchema>;

export type UserRole = 'owner' | 'accountant' | 'auditor' | 'viewer';
