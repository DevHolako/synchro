// Values mirror App\Enums\Permission (PHP); add a case there first, then mirror it here.
export const Permission = {
    ViewUsers: 'view:users',
    ManageUsers: 'manage:users',
    ImportReferentials: 'import:referentials',
    DeclareUnavailability: 'declare:unavailability',
    ReviewUnavailability: 'review:unavailability',
    ViewSchedules: 'view:schedules',
    BrowseSchedules: 'browse:schedules',
} as const;

export type PermissionValue = (typeof Permission)[keyof typeof Permission];
