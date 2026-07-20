import { Form, Head, Link } from '@inertiajs/react';
import { Download, FileSpreadsheet } from 'lucide-react';
import EmptyState from '@/components/empty-state';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import reports from '@/routes/management/reports';

type ReportOption = { key: string; name: string; description: string };
type FilterOption = { value: number; label: string };

type Props = {
    reports: ReportOption[];
    current: ReportOption;
    headings: string[];
    rows: (string | number)[][];
    total: number;
    truncated: boolean;
    previewLimit: number;
    filters: Record<string, string | undefined>;
    period: string;
    options: {
        students: FilterOption[];
        teachers: FilterOption[];
        classes: FilterOption[];
        plans: FilterOption[];
    };
};

const FILTER_FIELDS: {
    name: 'student_id' | 'teacher_id' | 'class_schedule_id' | 'plan_id';
    label: string;
    optionsKey: keyof Props['options'];
}[] = [
    { name: 'student_id', label: 'Aluno', optionsKey: 'students' },
    { name: 'teacher_id', label: 'Professor', optionsKey: 'teachers' },
    { name: 'class_schedule_id', label: 'Aula', optionsKey: 'classes' },
    { name: 'plan_id', label: 'Plano', optionsKey: 'plans' },
];

export default function Reports({
    reports: available,
    current,
    headings,
    rows,
    total,
    truncated,
    previewLimit,
    filters,
    period,
    options,
}: Props) {
    const exportQuery = {
        from: filters.from,
        to: filters.to,
        student_id: filters.student_id,
        teacher_id: filters.teacher_id,
        class_schedule_id: filters.class_schedule_id,
        plan_id: filters.plan_id,
    };

    return (
        <>
            <Head title="Relatórios" />

            <div className="mx-auto w-full max-w-6xl space-y-6 p-4">
                <header>
                    <h1 className="text-2xl font-bold">Relatórios</h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Escolha um relatório, ajuste o período e baixe em
                        planilha.
                    </p>
                </header>

                <nav aria-label="Relatórios" className="flex flex-wrap gap-2">
                    {available.map((report) => (
                        <Link
                            key={report.key}
                            href={reports.index({
                                query: { ...exportQuery, report: report.key },
                            })}
                            className={cn(
                                'min-h-10 rounded-lg border px-3 py-2 text-sm font-medium transition-colors',
                                current.key === report.key
                                    ? 'border-primary bg-primary text-primary-foreground'
                                    : 'bg-background hover:bg-accent',
                            )}
                        >
                            {report.name}
                        </Link>
                    ))}
                </nav>

                <section className="space-y-4 rounded-xl border bg-card p-4">
                    <div>
                        <h2 className="text-lg font-semibold">
                            {current.name}
                        </h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {current.description}
                        </p>
                    </div>

                    {/* A GET form: the filters live in the URL, so a filtered
                        report can be bookmarked and shared. */}
                    <Form
                        {...reports.index.form()}
                        method="get"
                        className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
                    >
                        {({ processing }) => (
                            <>
                                <input
                                    type="hidden"
                                    name="report"
                                    value={current.key}
                                />

                                <div className="grid gap-2">
                                    <Label htmlFor="from">De</Label>
                                    <Input
                                        id="from"
                                        name="from"
                                        type="date"
                                        defaultValue={filters.from}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="to">Até</Label>
                                    <Input
                                        id="to"
                                        name="to"
                                        type="date"
                                        defaultValue={filters.to}
                                    />
                                </div>

                                {FILTER_FIELDS.map((field) => (
                                    <div
                                        key={field.name}
                                        className="grid gap-2"
                                    >
                                        <Label htmlFor={field.name}>
                                            {field.label}
                                        </Label>
                                        <select
                                            id={field.name}
                                            name={field.name}
                                            defaultValue={
                                                filters[field.name] ?? ''
                                            }
                                            className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-base shadow-xs focus-visible:ring-[3px] focus-visible:ring-ring focus-visible:outline-none md:text-sm"
                                        >
                                            <option value="">Todos</option>
                                            {options[field.optionsKey].map(
                                                (option) => (
                                                    <option
                                                        key={option.value}
                                                        value={option.value}
                                                    >
                                                        {option.label}
                                                    </option>
                                                ),
                                            )}
                                        </select>
                                    </div>
                                ))}

                                <div className="flex items-end gap-2 lg:col-span-3">
                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        data-test="apply-filters-button"
                                    >
                                        Aplicar filtros
                                    </Button>
                                    <Button variant="secondary" asChild>
                                        <a
                                            href={reports.export.url(
                                                { report: current.key },
                                                { query: exportQuery },
                                            )}
                                            data-test="export-button"
                                        >
                                            <Download className="size-4" />
                                            Baixar planilha
                                        </a>
                                    </Button>
                                </div>
                            </>
                        )}
                    </Form>
                </section>

                <section className="space-y-3">
                    <div className="flex flex-wrap items-baseline justify-between gap-2">
                        <p className="text-sm text-muted-foreground">
                            {period} · {total}{' '}
                            {total === 1 ? 'linha' : 'linhas'}
                        </p>
                        {truncated && (
                            <p className="text-sm text-muted-foreground">
                                Mostrando as primeiras {previewLimit}. A
                                planilha traz todas.
                            </p>
                        )}
                    </div>

                    {rows.length === 0 ? (
                        <EmptyState
                            icon={FileSpreadsheet}
                            title="Nada no período"
                            description="Não há dados para este relatório com os filtros escolhidos."
                        />
                    ) : (
                        <div className="overflow-x-auto rounded-xl border">
                            <table className="w-full text-sm">
                                <thead className="bg-muted/50">
                                    <tr>
                                        {headings.map((heading) => (
                                            <th
                                                key={heading}
                                                scope="col"
                                                className="px-3 py-2 text-left font-medium whitespace-nowrap"
                                            >
                                                {heading}
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {rows.map((row, index) => (
                                        <tr
                                            key={index}
                                            className="border-t hover:bg-muted/30"
                                        >
                                            {row.map((cell, cellIndex) => (
                                                <td
                                                    key={cellIndex}
                                                    className="px-3 py-2 whitespace-nowrap"
                                                >
                                                    {cell}
                                                </td>
                                            ))}
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </section>
            </div>
        </>
    );
}

Reports.layout = {
    breadcrumbs: [{ title: 'Painel', href: dashboard() }],
};
