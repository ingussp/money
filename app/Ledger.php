<?php
declare(strict_types=1);

final class Ledger
{
    public static function visibility(string $alias = 'e'): array
    {
        return can_manage() ? ['', []] : [" AND $alias.created_by=? AND $alias.type='expense'", [current_user()['id']]];
    }

    public static function entry(int $id): array
    {
        $entry = scoped('entries', $id);
        if (!can_manage() && ($entry['type'] !== 'expense' || (int) $entry['created_by'] !== (int) current_user()['id'])) abort_request(403, 'You do not have access to this entry.');
        return $entry;
    }

    public static function editable(array $entry): bool
    {
        return in_array($entry['status'], ['draft', 'rejected'], true)
            && !Database::one('SELECT id FROM invoices WHERE entry_id=?', [$entry['id']])
            && (can_manage() || (int) $entry['created_by'] === (int) current_user()['id']);
    }

    public static function summary(string $start, string $end): array
    {
        [$scope, $params] = self::visibility();
        $totals = Database::one("SELECT COALESCE(SUM(CASE WHEN type='income' THEN amount ELSE 0 END),0) income, COALESCE(SUM(CASE WHEN type='expense' THEN amount ELSE 0 END),0) expenses FROM entries e WHERE workspace_id=? AND status='paid' AND paid_on>=? AND paid_on<? $scope", [workspace_id(), $start, $end, ...$params]);
        $totals['balance'] = (int) $totals['income'] - (int) $totals['expenses'];
        return $totals;
    }

    public static function months(int $count = 6): array
    {
        $first = new DateTimeImmutable('first day of this month');
        $months = [];
        for ($i = $count - 1; $i >= 0; --$i) {
            $date = $first->modify("-$i months");
            $months[] = ['label' => $date->format('M Y')] + self::summary($date->format('Y-m-d'), $date->modify('+1 month')->format('Y-m-d'));
        }
        return $months;
    }
}
