export function PupilIdentity({
  number,
  name,
  id,
}: {
  number: number;
  name: string;
  id: string;
}) {
  return (
    <div className="flex min-w-0 items-center gap-3">
      <span className="w-5 shrink-0 text-sm text-muted-foreground tabular-nums">
        {number}
      </span>
      <div className="min-w-0">
        <span className="block text-base font-semibold max-[760px]:text-sm">
          {name}
        </span>
        <span className="block text-sm text-muted-foreground">{id}</span>
      </div>
    </div>
  );
}
