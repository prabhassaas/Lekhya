import { Controller, Post, Get, Body, UseGuards, Request } from '@nestjs/common';
import { AuthService } from './auth.service';
import { JwtAuthGuard } from './jwt-auth.guard';

@Controller('auth')
export class AuthController {
  constructor(private readonly authService: AuthService) {}

  @Post('login')
  async login(@Body() body: { email: string; password: string }) {
    return this.authService.login(body.email, body.password);
  }

  @UseGuards(JwtAuthGuard)
  @Get('me')
  async me(@Request() req: { user: { sub: string; tenantId: string } }) {
    const user = await this.authService.getUserById(req.user.sub, req.user.tenantId);
    if (!user) return null;
    const { password_hash: _ph, ...safe } = user;
    return safe;
  }
}
