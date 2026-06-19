import { Injectable, NestMiddleware } from '@nestjs/common';
import { Request, Response, NextFunction } from 'express';
import * as jwt from 'jsonwebtoken';

interface JwtPayload {
  tenantId?: string;
}

@Injectable()
export class TenantMiddleware implements NestMiddleware {
  use(req: Request, res: Response, next: NextFunction) {
    const authHeader = req.headers['authorization'];
    if (authHeader?.startsWith('Bearer ')) {
      const token = authHeader.slice(7);
      try {
        const secret = process.env.JWT_SECRET ?? 'dev-secret';
        const payload = jwt.verify(token, secret) as JwtPayload;
        if (payload.tenantId) {
          (req as Request & { tenantId?: string }).tenantId = payload.tenantId;
        }
      } catch {
        // Invalid token — let the JWT guard handle it
      }
    }
    next();
  }
}
