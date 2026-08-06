interface PageHeaderProps {
    title: string;
    description: string;
}

export function PageHeader({ title, description }: PageHeaderProps) {
    return (
        <div className="flex flex-col gap-1">
            <h2 className="text-2xl font-semibold">{title}</h2>
            <p className="text-muted-foreground text-sm">{description}</p>
        </div>
    );
}
