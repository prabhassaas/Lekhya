/** @type {import('next').NextConfig} */
const nextConfig = {
  output: 'standalone',
  transpilePackages: ['@lekhya/shared'],
};

module.exports = nextConfig;
