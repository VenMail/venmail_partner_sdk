<div class="venmail-dashboard">
    <h3>Venmail Email Setup for {$domain|escape:'htmlall'}</h3>

    {if isset($error) && $error}
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> {$error|escape:'htmlall'}
        </div>
    {/if}

    {if isset($verification.fully_verified) && $verification.fully_verified}
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> Your domain is fully verified and active!
        </div>
    {else}
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> Please configure the DNS records below to activate your email service.
        </div>
    {/if}

    {if $records && count($records) > 0}
        <h4>Required DNS Records</h4>
        <p class="text-muted">Add these records at your domain registrar (e.g. Cloudflare, GoDaddy, Namecheap).</p>
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th style="width:80px">Type</th>
                        <th>Name / Host</th>
                        <th>Value / Points To</th>
                        <th style="width:140px">Purpose</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach from=$records item=record}
                        <tr>
                            <td><span class="label label-info">{$record.type|escape:'htmlall'}</span></td>
                            <td><code style="font-size:12px">{$record.name|escape:'htmlall'}</code></td>
                            <td><code style="font-size:12px;word-break:break-all">{$record.value|escape:'htmlall'}</code></td>
                            <td>{$record.purpose|escape:'htmlall'}</td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    {/if}

    <div class="row" style="margin-top:20px">
        <div class="col-sm-6">
            <h4>Verification Status</h4>
            <ul class="list-group">
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    Domain (CNAME) Verified
                    {if isset($verification.cname_verified) && $verification.cname_verified}
                        <span class="label label-success"><i class="fas fa-check"></i> Yes</span>
                    {else}
                        <span class="label label-danger"><i class="fas fa-times"></i> No</span>
                    {/if}
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    DKIM Verified
                    {if isset($verification.dkim_verified) && $verification.dkim_verified}
                        <span class="label label-success"><i class="fas fa-check"></i> Yes</span>
                    {else}
                        <span class="label label-danger"><i class="fas fa-times"></i> No</span>
                    {/if}
                </li>
            </ul>
        </div>
        <div class="col-sm-6">
            <h4>Quick Actions</h4>
            <p>Access your full email dashboard to manage mailboxes, contacts, calendars, and more.</p>
            <p class="text-muted small">Use the "Login to Venmail" button above this panel for single sign-on access.</p>
        </div>
    </div>
</div>
