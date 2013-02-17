{nocache}
{if Authentication::IsLoggedIn()}
    You are logged in as <strong>{Authentication::GetUsername()}</strong>. {actlink text=Logout controller=Account action=Logout}
{else}
    You are not logged in. {actlink text=Login controller=Account action=Login} / {actlink text=Register controller=Account action=Register}
{/if}
{/nocache}