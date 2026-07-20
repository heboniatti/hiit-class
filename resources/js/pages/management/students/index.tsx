import { Form, Head, Link } from '@inertiajs/react';
import { CircleAlert, Search, UserPlus, Users } from 'lucide-react';
import EmptyState from '@/components/empty-state';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatPhone } from '@/lib/phone';
import { dashboard } from '@/routes';
import students from '@/routes/management/students';

type StudentRow = {
    id: number;
    name: string;
    phone: string;
    status: string;
    is_active: boolean;
    plan_name: string | null;
    has_overdue: boolean;
    registered_at: string;
};

type Props = {
    students: StudentRow[];
    search: string;
};

export default function StudentsIndex({ students: rows, search }: Props) {
    return (
        <>
            <Head title="Alunos" />

            <div className="mx-auto w-full max-w-4xl space-y-4 p-4">
                <header className="flex flex-wrap items-center justify-between gap-3">
                    <h1 className="text-2xl font-bold">Alunos</h1>
                    <Button asChild>
                        <Link href={students.create()}>
                            <UserPlus className="size-4" />
                            Cadastrar aluno
                        </Link>
                    </Button>
                </header>

                <Form {...students.index.form()} className="flex gap-2">
                    <Input
                        name="search"
                        defaultValue={search}
                        placeholder="Buscar por nome ou telefone"
                        className="h-11"
                    />
                    <Button type="submit" variant="secondary" className="h-11">
                        <Search className="size-4" />
                        Buscar
                    </Button>
                </Form>

                {rows.length === 0 ? (
                    <EmptyState
                        icon={Users}
                        title="Nenhum aluno encontrado"
                        description="Ajuste a busca ou cadastre um novo aluno."
                    />
                ) : (
                    <ul className="space-y-3">
                        {rows.map((row) => (
                            <li key={row.id}>
                                <Link
                                    href={students.show(row.id)}
                                    className="flex items-center justify-between gap-3 rounded-xl border bg-card p-4 transition-colors hover:border-primary/50"
                                >
                                    <div className="min-w-0">
                                        <p className="truncate font-semibold">
                                            {row.name}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {formatPhone(row.phone)} ·{' '}
                                            {row.plan_name ?? 'Sem plano'}
                                        </p>
                                    </div>
                                    <div className="flex shrink-0 items-center gap-2">
                                        {row.has_overdue && (
                                            <Badge
                                                variant="destructive"
                                                className="gap-1"
                                            >
                                                <CircleAlert className="size-3" />
                                                Vencido
                                            </Badge>
                                        )}
                                        {!row.is_active && (
                                            <Badge variant="secondary">
                                                {row.status}
                                            </Badge>
                                        )}
                                        {!row.plan_name && row.is_active && (
                                            <Badge variant="secondary">
                                                Sem plano
                                            </Badge>
                                        )}
                                    </div>
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </>
    );
}

StudentsIndex.layout = {
    breadcrumbs: [{ title: 'Painel', href: dashboard() }],
};
