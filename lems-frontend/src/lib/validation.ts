import { table } from "./db";

/*
 * Laravel-compatible validator. Replicates the exact rule set and default
 * English messages used by the Laravel API controllers:
 *   required, sometimes, nullable, string, numeric, min:N, max:N, in:a,b,
 *   exists:table,column, unique:table,column[,exceptId], confirmed
 * Also replicates the global middleware: TrimStrings (skips password fields)
 * and ConvertEmptyStringsToNull.
 */

export type Rules = Record<string, string>;
export interface ValidationResult {
  data: Record<string, any>;
  errors: Record<string, string[]> | null;
}

const PASSWORD_FIELDS = ["current_password", "password", "password_confirmation"];

/** TrimStrings + ConvertEmptyStringsToNull. */
export function preprocess(body: Record<string, any>): Record<string, any> {
  const out: Record<string, any> = {};
  for (const [key, value] of Object.entries(body)) {
    let val = value;
    if (typeof val === "string") {
      if (!PASSWORD_FIELDS.includes(key)) val = val.trim();
      if (val === "") val = null;
    }
    out[key] = val;
  }
  return out;
}

const attr = (field: string) => field.replace(/_/g, " ");

const isEmpty = (v: any) => v === null || v === undefined || v === "";

function ruleSpec(parts: string[], name: string): string | undefined {
  const p = parts.find((x) => x === name || x.startsWith(`${name}:`));
  return p && p.includes(":") ? p.slice(p.indexOf(":") + 1) : undefined;
}

/**
 * Coerce id-like lookups to numbers: MySQL compares `id = '3'` fine but
 * Prisma's BigInt filters reject strings. Non-id columns keep the raw value.
 */
function lookupValue(col: string, value: any): any {
  const isIdCol = col === "id" || col.endsWith("_id");
  return isIdCol && /^-?\d+$/.test(String(value)) ? Number(value) : value;
}

async function existsInDb(
  tableCol: string,
  value: any
): Promise<boolean> {
  const [tableName, col = "id"] = tableCol.split(",");
  try {
    const count = await table(tableName).count({
      where: { [col]: lookupValue(col, value) },
    });
    return count > 0;
  } catch {
    // MySQL coerces mismatched scalars to 0 matches; Prisma throws instead.
    return false;
  }
}

async function uniqueInDb(tableCol: string, value: any): Promise<boolean> {
  const [tableName, col = "id", exceptRaw] = tableCol.split(",");
  const where: Record<string, any> = { [col]: lookupValue(col, value) };
  if (exceptRaw !== undefined && exceptRaw !== "" && /^\d+$/.test(exceptRaw)) {
    where.id = { not: Number(exceptRaw) };
  }
  try {
    const count = await table(tableName).count({ where });
    return count > 0;
  } catch {
    return false;
  }
}

/**
 * Validate input against rule strings.
 * @param updateId id excluded from `unique:...,....,{id}` checks (route param).
 */
export async function validate(
  body: Record<string, any>,
  rules: Rules,
  updateId?: string | number
): Promise<ValidationResult> {
  const input = preprocess(body);
  const data: Record<string, any> = {};
  const errors: Record<string, string[]> = {};

  for (const [field, ruleString] of Object.entries(rules)) {
    const parts = ruleString.split("|").filter(Boolean);
    const present = Object.prototype.hasOwnProperty.call(input, field);
    const value = present ? input[field] : undefined;

    // Laravel: absent attributes are not validated unless required/sometimes.
    if (!present) {
      if (parts.includes("sometimes")) continue;
      if (parts.includes("required")) {
        errors[field] = [`The ${attr(field)} field is required.`];
      }
      continue;
    }

    const messages: string[] = [];
    const nullable = parts.includes("nullable");
    const required = parts.includes("required");
    const isNumericRule = parts.includes("numeric");

    if (isEmpty(value)) {
      if (required) messages.push(`The ${attr(field)} field is required.`);
      else if (nullable) {
        data[field] = null;
        continue;
      } else if (parts.some((p) => p.startsWith("in:"))) {
        messages.push(`The selected ${attr(field)} is invalid.`);
      } else {
        messages.push(`The ${attr(field)} field is required.`);
      }
    } else {
      for (const part of parts) {
        const [name, spec] = [part.split(":")[0], part.slice(part.indexOf(":") + 1)];

        switch (name) {
          case "required":
          case "sometimes":
          case "nullable":
            break;
          case "string":
            if (typeof value !== "string") {
              messages.push(`The ${attr(field)} must be a string.`);
            }
            break;
          case "numeric":
            if (value === null || value === "" || isNaN(Number(value))) {
              messages.push(`The ${attr(field)} must be a number.`);
            }
            break;
          case "min": {
            const n = Number(spec);
            if (isNumericRule) {
              if (Number(value) < n) messages.push(`The ${attr(field)} must be at least ${n}.`);
            } else if (String(value).length < n) {
              messages.push(`The ${attr(field)} must be at least ${n} characters.`);
            }
            break;
          }
          case "max": {
            const n = Number(spec);
            if (isNumericRule) {
              if (Number(value) > n) {
                messages.push(`The ${attr(field)} may not be greater than ${n}.`);
              }
            } else if (String(value).length > n) {
              messages.push(`The ${attr(field)} may not be greater than ${n} characters.`);
            }
            break;
          }
          case "in": {
            const allowed = spec.split(",");
            if (!allowed.includes(String(value))) {
              messages.push(`The selected ${attr(field)} is invalid.`);
            }
            break;
          }
          case "exists": {
            if (!(await existsInDb(spec, value))) {
              messages.push(`The selected ${attr(field)} is invalid.`);
            }
            break;
          }
          case "unique": {
            const except =
              spec.includes("{id}") && updateId !== undefined
                ? spec.replace("{id}", String(updateId))
                : spec;
            // uniqueInDb returns true when a conflicting row exists.
            if (await uniqueInDb(except, value)) {
              messages.push(`The ${attr(field)} has already been taken.`);
            }
            break;
          }
          case "confirmed": {
            if (input[`${field}_confirmation`] !== value) {
              messages.push(`The ${attr(field)} confirmation does not match.`);
            }
            break;
          }
        }
      }
    }

    if (messages.length > 0) errors[field] = messages;
    else data[field] = value;
  }

  return { data, errors: Object.keys(errors).length > 0 ? errors : null };
}
