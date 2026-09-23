<?php

namespace App\Http\Controllers;

use App\Models\Complaint;

class HomeController extends Controller
{
    public function index()
    {
        $stats = [
            "totalComplaints" => Complaint::count(),
            "resolvedComplaints" => Complaint::where(
                "status",
                "Resolved",
            )->count(),
            "pendingComplaints" => Complaint::whereIn("status", [
                "Submitted",
                "Under Review",
                "In Progress",
            ])->count(),
            "avgDays" => $this->getAverageResolutionDays(),
        ];

        if (auth()->check()) {
            return view("pages.home.dashboard", $stats);
        }

        return view("pages.home.guest", $stats);
    }

    private function getAverageResolutionDays(): string
    {
        $avg = Complaint::where("status", "Resolved")
            ->whereNotNull("updated_at")
            ->selectRaw(
                "AVG(EXTRACT(EPOCH FROM (updated_at - created_at)) / 86400) as avg_days",
            )
            ->value("avg_days");

        return $avg !== null ? (string) round((float) $avg) : "—";
    }
}
