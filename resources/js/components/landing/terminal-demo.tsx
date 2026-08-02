import { CodeBlock } from '@/components/code-block';
import { CodeTabs } from '@/components/code-tabs';
import { BorderBeam } from '@/components/ui/border-beam';
import { ShineBorder } from '@/components/ui/shine-border';

const curlExample = `curl -X POST "https://api.troth.dev/v1/verify/age" \\
  -H "Authorization: Bearer sk_sandbox_..." \\
  -H "Content-Type: application/json" \\
  -d '{"age":18,"type":"equal_above","subject":"fayda-test-a"}'`;

const jsExample = `const response = await fetch(
  'https://api.troth.dev/v1/verify/age',
  {
    method: 'POST',
    headers: {
      'Authorization': 'Bearer sk_sandbox_...',
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      age: 18,
      type: 'equal_above',
      subject: 'fayda-test-a',
    }),
  },
);

const data = await response.json();
console.log(data.result); // true`;

const pythonExample = `import requests

response = requests.post(
    'https://api.troth.dev/v1/verify/age',
    headers={'Authorization': 'Bearer sk_sandbox_...'},
    json={
        'age': 18,
        'type': 'equal_above',
        'subject': 'fayda-test-a',
    },
)

print(response.json()['result'])  # True`;

const responseExample = `{
  "result": true,
  "query_id": "6f0e2a3b-9c1d-4e5f-a678-b90123c4d5e6",
  "verified_at": "2026-08-02T12:00:00+00:00",
  "expires_at": "2026-10-31T12:00:00+00:00"
}`;

export default function LandingTerminal() {
    return (
        <section className="relative mx-auto flex w-full max-w-6xl flex-col items-center gap-8 px-6 py-16">
            <div className="flex flex-col items-center gap-4 text-center">
                <span className="bg-accent/10 border-accent/30 text-accent rounded-full border px-4 py-1.5 text-xs font-semibold uppercase tracking-wider">
                    Developer experience
                </span>
                <h2 className="max-w-2xl text-balance text-3xl font-bold tracking-tight sm:text-4xl">
                    One endpoint. Zero guessing.
                </h2>
                <p className="text-muted-foreground max-w-xl text-pretty">
                    A single POST returns a boolean answer - the only data you
                    should ever hold about someone&apos;s age.
                </p>
            </div>

            <div className="relative w-full max-w-3xl">
                <div className="bg-background/40 relative flex w-full flex-col overflow-hidden rounded-2xl p-6 backdrop-blur-sm sm:p-8">
                    <div className="mb-5 flex flex-wrap items-center justify-between gap-3">
                        <span className="text-muted-foreground font-mono text-xs">
                            POST /v1/verify/age
                        </span>
                        <span className="text-primary inline-flex items-center gap-1.5 text-xs font-semibold">
                            <span className="bg-primary/70 size-1.5 animate-pulse rounded-full" />
                            Sandbox ready
                        </span>
                    </div>

                    <CodeTabs
                        items={[
                            { language: 'curl', code: curlExample },
                            { language: 'js', code: jsExample },
                            { language: 'python', code: pythonExample },
                        ]}
                    />

                    <div className="mt-4">
                        <CodeBlock
                            code={responseExample}
                            language="json"
                            showHeader={false}
                            className="rounded-lg border border-emerald-500/20 bg-emerald-500/[0.04]"
                        />
                    </div>

                    <div className="text-muted-foreground mt-4 flex flex-wrap items-center justify-between gap-3 text-xs">
                        <span className="inline-flex items-center gap-1.5">
                            <span className="size-1.5 rounded-full bg-emerald-500" />
                            200 OK · result: true
                        </span>
                        <span>Cache until expires_at · keep query_id for audits</span>
                    </div>
                </div>
                <ShineBorder
                    className="rounded-2xl"
                    borderWidth={1}
                    duration={10}
                    shineColor={['#FF2D78', '#7928CA', '#00D4A0']}
                />
                <BorderBeam size={120} duration={6} />
            </div>
        </section>
    );
}
