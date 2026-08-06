import { Head, router } from '@inertiajs/react';
import {
    CheckCircle2,
    Mail,
    Send,
    Trash2,
    UserPlus,
    Users,
} from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { dashboard } from '@/routes';
import { index as teamsIndex } from '@/routes/teams';
import { store as storeInvitation } from '@/routes/teams/invitations';
import {
    destroy as destroyInvitation,
    resend,
} from '@/routes/teams/invitations';
import {
    destroy as destroyMember,
    update as updateMember,
} from '@/routes/teams/members';
import type { TeamInfo, TeamInvitation, TeamMember, TeamRole } from '@/types';

type Props = {
    team: TeamInfo;
    role: TeamRole;
    is_owner: boolean;
    members: TeamMember[];
    invitations: TeamInvitation[];
};

const roleLabels: Record<TeamRole, string> = {
    owner: 'Owner',
    developer: 'Developer',
    viewer: 'Viewer',
};

const roleDescriptions: Record<TeamRole, string> = {
    owner: 'Full access, including member management and deletion.',
    developer: 'Full CRUD on applications, keys, and webhooks.',
    viewer: 'Read-only access to applications and logs.',
};

function roleBadgeVariant(role: TeamRole): 'default' | 'secondary' {
    return role === 'owner' ? 'default' : 'secondary';
}

function InviteDialog({
    onInvite,
}: {
    onInvite: (email: string, role: TeamRole) => void;
}) {
    const [email, setEmail] = useState('');
    const [role, setRole] = useState<TeamRole>('developer');

    function submit() {
        if (!email.trim()) {
            return;
        }

        onInvite(email.trim(), role);
        setEmail('');
    }

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button>
                    <UserPlus />
                    Invite member
                </Button>
            </DialogTrigger>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Invite a team member</DialogTitle>
                    <DialogDescription>
                        They'll receive an email with a link to join the team.
                    </DialogDescription>
                </DialogHeader>
                <div className="space-y-4">
                    <div className="grid gap-2">
                        <Label htmlFor="invite-email">Email address</Label>
                        <Input
                            id="invite-email"
                            type="email"
                            placeholder="teammate@company.com"
                            value={email}
                            onChange={(event) => setEmail(event.target.value)}
                        />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="invite-role">Role</Label>
                        <Select
                            value={role}
                            onValueChange={(value) =>
                                setRole(value as TeamRole)
                            }
                        >
                            <SelectTrigger id="invite-role" className="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {(['developer', 'viewer'] as TeamRole[]).map(
                                    (candidate) => (
                                        <SelectItem
                                            key={candidate}
                                            value={candidate}
                                        >
                                            {roleLabels[candidate]}
                                        </SelectItem>
                                    ),
                                )}
                            </SelectContent>
                        </Select>
                        <p className="text-muted-foreground text-sm">
                            {roleDescriptions[role]}
                        </p>
                    </div>
                    <div className="flex justify-end">
                        <Button onClick={submit} disabled={!email.trim()}>
                            <Send />
                            Send invitation
                        </Button>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}

export default function Teams({
    team,
    role,
    is_owner,
    members,
    invitations,
}: Props) {
    function invite(email: string, inviteRole: TeamRole) {
        router.post(
            storeInvitation().url,
            { email, role: inviteRole },
            { preserveScroll: true },
        );
    }

    function changeRole(member: TeamMember, newRole: TeamRole) {
        router.patch(
            updateMember(member.id).url,
            { role: newRole },
            { preserveScroll: true },
        );
    }

    function removeMember(member: TeamMember) {
        if (!confirm(`Remove ${member.name} from the team?`)) {
            return;
        }

        router.delete(destroyMember(member.id).url, { preserveScroll: true });
    }

    function resendInvitation(invitation: TeamInvitation) {
        router.post(resend(invitation.id).url, {}, { preserveScroll: true });
    }

    function cancelInvitation(invitation: TeamInvitation) {
        router.delete(destroyInvitation(invitation.id).url, {
            preserveScroll: true,
        });
    }

    return (
        <>
            <Head title="Team" />

            <div className="flex flex-col gap-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 className="flex items-center gap-2 text-2xl font-semibold">
                            <Users className="text-muted-foreground size-6" />
                            {team.name}
                        </h2>
                        <p className="text-muted-foreground text-sm">
                            Owned by {team.owner_name} · {team.member_count}{' '}
                            {team.member_count === 1 ? 'member' : 'members'} ·
                            You are the {roleLabels[role]}
                        </p>
                    </div>
                    {is_owner && <InviteDialog onInvite={invite} />}
                </div>

                {!is_owner && (
                    <Card>
                        <CardContent className="pt-6">
                            <p className="text-muted-foreground text-sm">
                                As a {roleLabels[role]}, you can view
                                applications and their logs. Only the owner can
                                manage members and invitations.
                            </p>
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle>Members</CardTitle>
                        <CardDescription>
                            Everyone with access to this team's applications.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Member</TableHead>
                                    <TableHead>Role</TableHead>
                                    <TableHead>Joined</TableHead>
                                    {is_owner && <TableHead className="w-32" />}
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {members.map((member) => (
                                    <TableRow key={member.id}>
                                        <TableCell>
                                            <div className="font-medium">
                                                {member.name}
                                            </div>
                                            <div className="text-muted-foreground text-sm">
                                                {member.email}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            {is_owner && !member.is_owner ? (
                                                <Select
                                                    value={member.role}
                                                    onValueChange={(value) =>
                                                        changeRole(
                                                            member,
                                                            value as TeamRole,
                                                        )
                                                    }
                                                >
                                                    <SelectTrigger className="w-36">
                                                        <SelectValue />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {(
                                                            [
                                                                'developer',
                                                                'viewer',
                                                            ] as TeamRole[]
                                                        ).map((candidate) => (
                                                            <SelectItem
                                                                key={candidate}
                                                                value={
                                                                    candidate
                                                                }
                                                            >
                                                                {
                                                                    roleLabels[
                                                                        candidate
                                                                    ]
                                                                }
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                            ) : (
                                                <Badge
                                                    variant={roleBadgeVariant(
                                                        member.role,
                                                    )}
                                                >
                                                    {roleLabels[member.role]}
                                                </Badge>
                                            )}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {member.joined_at
                                                ? new Date(
                                                      member.joined_at,
                                                  ).toLocaleDateString(
                                                      undefined,
                                                      {
                                                          year: 'numeric',
                                                          month: 'short',
                                                          day: 'numeric',
                                                      },
                                                  )
                                                : '—'}
                                        </TableCell>
                                        {is_owner && (
                                            <TableCell>
                                                {!member.is_owner && (
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        aria-label={`Remove ${member.name}`}
                                                        onClick={() =>
                                                            removeMember(member)
                                                        }
                                                    >
                                                        <Trash2 className="text-destructive size-4" />
                                                    </Button>
                                                )}
                                            </TableCell>
                                        )}
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Pending invitations</CardTitle>
                        <CardDescription>
                            Invitations that haven't been accepted yet.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {invitations.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                No pending invitations.
                            </p>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Email</TableHead>
                                        <TableHead>Role</TableHead>
                                        <TableHead>Status</TableHead>
                                        {is_owner && (
                                            <TableHead className="w-36" />
                                        )}
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {invitations.map((invitation) => (
                                        <TableRow key={invitation.id}>
                                            <TableCell className="font-medium">
                                                {invitation.email}
                                            </TableCell>
                                            <TableCell>
                                                <Badge
                                                    variant={roleBadgeVariant(
                                                        invitation.role,
                                                    )}
                                                >
                                                    {
                                                        roleLabels[
                                                            invitation.role
                                                        ]
                                                    }
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                {invitation.is_expired ? (
                                                    <Badge variant="secondary">
                                                        Expired
                                                    </Badge>
                                                ) : (
                                                    <span className="text-muted-foreground flex items-center gap-1 text-sm">
                                                        <Mail className="size-3.5" />
                                                        Expires{' '}
                                                        {new Date(
                                                            invitation.expires_at,
                                                        ).toLocaleDateString(
                                                            undefined,
                                                            {
                                                                month: 'short',
                                                                day: 'numeric',
                                                            },
                                                        )}
                                                    </span>
                                                )}
                                            </TableCell>
                                            {is_owner && (
                                                <TableCell>
                                                    <div className="flex justify-end gap-1">
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() =>
                                                                resendInvitation(
                                                                    invitation,
                                                                )
                                                            }
                                                        >
                                                            Resend
                                                        </Button>
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            aria-label="Cancel invitation"
                                                            onClick={() =>
                                                                cancelInvitation(
                                                                    invitation,
                                                                )
                                                            }
                                                        >
                                                            <Trash2 className="text-destructive size-4" />
                                                        </Button>
                                                    </div>
                                                </TableCell>
                                            )}
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                    {invitations.length > 0 && (
                        <CardFooter className="text-muted-foreground flex items-center gap-2 text-sm">
                            <CheckCircle2 className="size-4" />
                            Invites expire 7 days after they're sent.
                        </CardFooter>
                    )}
                </Card>
            </div>
        </>
    );
}

Teams.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
        {
            title: 'Team',
            href: teamsIndex(),
        },
    ],
};
