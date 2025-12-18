<?php

declare(strict_types=1);

namespace App\Project\View;

use App\Entity\Project;

final class ProjectViewFactory
{
    public function make(Project $project): array
    {
        $teamLeadIds = [];
        foreach ($project->getTeamLeads() as $user) {
            $teamLeadIds[] = $user->getId()?->toRfc4122();
        }

        return [
            'id' => $project->getId()?->toRfc4122(),
            'name' => $project->getName(),
            'description' => $project->getDescription(),
            'created_by_user_id' => $project->getCreatedByUser()?->getId()?->toRfc4122(),
            'created_at' => $project->getCreatedAt()?->format(DATE_ATOM),
            'teamlead_user_ids' => array_values(array_filter($teamLeadIds)),
            'is_completed' => $project->isCompleted(),
            'completed_at' => $project->getCompletedAt()?->format(DATE_ATOM),
            'completed_by_user_id' => $project->getCompletedByUser()?->getId()?->toRfc4122(),
        ];
    }
}
