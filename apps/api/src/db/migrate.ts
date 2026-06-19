import { Pool } from 'pg';
import * as fs from 'fs';
import * as path from 'path';

async function migrate() {
  const pool = new Pool({
    connectionString: process.env.DATABASE_URL,
  });

  // __dirname is dist/db at runtime; migrations are copied there by Dockerfile
  const migrationPath = path.join(__dirname, 'migrations', '001_init.sql');
  const sql = fs.readFileSync(migrationPath, 'utf-8');

  const client = await pool.connect();
  try {
    await client.query(sql);
    console.log('Migration completed successfully');
  } finally {
    client.release();
    await pool.end();
  }
}

migrate().catch((err) => {
  console.error('Migration failed:', err);
  process.exit(1);
});
