import { Controller, Get, Param, UseGuards, NotFoundException } from '@nestjs/common';
import { TenantsService } from './tenants.service';
import { JwtAuthGuard } from '../auth/jwt-auth.guard';

@UseGuards(JwtAuthGuard)
@Controller('tenants')
export class TenantsController {
  constructor(private readonly tenantsService: TenantsService) {}

  @Get(':id')
  async findOne(@Param('id') id: string) {
    const tenant = await this.tenantsService.findById(id);
    if (!tenant) throw new NotFoundException('Tenant not found');
    return tenant;
  }
}
