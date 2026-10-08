"use client";

import { usePtmsReport } from "@/hooks/use-ptms-reports";
import { PageHeader } from "@/components/page-header";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { LoadingPage } from "@/components/loading-page";
import { useRouter } from "next/navigation";
import { ArrowLeft } from "lucide-react";

export default function PtmsReportDetailPage({ params }: { params: { id: string } }) {
  const { id } = params;
  const router = useRouter();
  const { data: report, isLoading } = usePtmsReport(Number(id));

  if (isLoading) return <LoadingPage />;

  if (!report) {
    return (
      <div className="space-y-6">
        <PageHeader title="Report Not Found" description="The requested PTMS report could not be found." />
        <Button variant="outline" onClick={() => router.push("/ptms")}>
          <ArrowLeft className="mr-2 h-4 w-4" /> Back to Reports
        </Button>
      </div>
    );
  }

  const statusVariant = report.status === "completed" ? "default" : report.status === "in_progress" ? "secondary" : "outline";

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-4">
          <Button variant="outline" size="icon" onClick={() => router.push("/ptms")}>
            <ArrowLeft className="h-4 w-4" />
          </Button>
          <div>
            <h1 className="text-2xl font-bold">PTMS Report #{report.report_number || report.id}</h1>
            <p className="text-muted-foreground">Production Time Measurement Study</p>
          </div>
        </div>
        <Badge variant={statusVariant}>{report.status}</Badge>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">Factory</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-lg font-semibold">{report.factory?.factory_name || "-"}</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">Department</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-lg font-semibold">{report.department?.department_name || "-"}</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">Production Line</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-lg font-semibold">{report.production_line?.line_name || "-"}</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">Article</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-lg font-semibold">{report.article?.article_name || "-"}</p>
          </CardContent>
        </Card>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">Operator</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-lg font-semibold">{report.operator?.operator_name || "-"}</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">Process Version</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-lg font-semibold">{report.process_version ? `v${report.process_version.version_number}` : "-"}</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">Report Date</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-lg font-semibold">{report.created_at ? new Date(report.created_at).toLocaleDateString() : "-"}</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">Created By</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-lg font-semibold">{report.creator?.name || "-"}</p>
          </CardContent>
        </Card>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">Machining TMU</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-2xl font-bold">{report.machining_tmu != null ? Number(report.machining_tmu).toFixed(2) : "-"}</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">Handling TMU</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-2xl font-bold">{report.handling_tmu != null ? Number(report.handling_tmu).toFixed(2) : "-"}</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">Total TMU</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-2xl font-bold">{report.total_tmu != null ? Number(report.total_tmu).toFixed(2) : "-"}</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">SMV</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-2xl font-bold">{report.smv != null ? Number(report.smv).toFixed(2) : "-"}</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">BMS</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-2xl font-bold">{report.bms != null ? Number(report.bms).toFixed(2) : "-"}</p>
          </CardContent>
        </Card>
      </div>

      {report.remark && (
        <Card>
          <CardHeader>
            <CardTitle>Remarks</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-muted-foreground">{report.remark}</p>
          </CardContent>
        </Card>
      )}
    </div>
  );
}
