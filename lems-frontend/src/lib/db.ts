import { PrismaClient } from "@prisma/client";

// Prisma singleton — connects to the EXISTING `lean_ie` MySQL database
// (see .env DATABASE_URL). Never wipe or recreate that database.
const globalForPrisma = globalThis as unknown as { prisma?: PrismaClient };

export const db = globalForPrisma.prisma ?? new PrismaClient();

if (process.env.NODE_ENV !== "production") globalForPrisma.prisma = db;

/** Dynamic delegate access for the data-driven CRUD registry (see entities.ts).
 * Model names are the introspected table names (e.g. db.factories). */
export const table = (name: string) => (db as any)[name];
