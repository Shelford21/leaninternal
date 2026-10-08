import { redirect } from "next/navigation";

/** Legacy /master-data/lines URL -> the Production Lines master. */
export default function LinesRedirectPage() {
  redirect("/master-data/production-lines");
}
