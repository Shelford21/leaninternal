/** @type {import('next').NextConfig} */
const nextConfig = {
  output: 'standalone',
  async rewrites() {
    // Safety-net proxy to the Laravel backend. This MUST be `fallback` (not the
    // default `afterFiles` form): default rewrites run BEFORE dynamic filesystem
    // routes and would shadow app/api/[resource] route handlers. A `fallback`
    // rewrite only matches paths no Next.js route handler claimed, so Laravel
    // is reached solely for endpoints not (yet) implemented in Next.js.
    const backendUrl = process.env.LEAN_API_URL || 'http://localhost:8000';
    return {
      fallback: [
        {
          source: '/api/:path*',
          destination: `${backendUrl}/api/:path*`,
        },
      ],
    };
  },
};
module.exports = nextConfig;
