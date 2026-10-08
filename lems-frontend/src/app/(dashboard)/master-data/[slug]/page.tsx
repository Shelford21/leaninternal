import { Suspense } from "react";
import { notFound } from "next/navigation";
import { MASTERS } from "@/lib/master-config";
import MasterTablePage from "@/components/master/master-table-page";

export default function MasterDataSlugPage({ params }: { params: { slug: string } }) {
  if (!MASTERS[params.slug]) notFound();
  return (
    <Suspense fallback={null}>
      <MasterTablePage slug={params.slug} />
    </Suspense>
  );
}
