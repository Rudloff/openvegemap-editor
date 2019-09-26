{include file="inc/head.tpl"}
{$msg}
<h2>{$properties.name}</h2>
<table>
    {if isset($properties.amenity)}
        <tr><th>Type</th><td>{$properties.amenity}</td></tr>
    {/if}
    {if isset($properties.website)}
        <tr><th>Website</th><td><a target="_blank" href="{$properties.website}">{$properties.website}</a></td></tr>
    {/if}
</table>
<div class="my1">
    <a target="_blank" class="btn--blue" href="https://openvegemap.netlib.re/#zoom=18&amp;lat={$coords.1}&amp;lon={$coords.0}">Display on map</a>
    <a target="_blank" class="btn--blue" href="http://www.openstreetmap.org/{$type}/{$id}">Open on OSM</a>
</div>
<form class="py1" method="post">
    <div class="grd">
        {foreach $editProperties as $property=>$label}
            <div class="grd-row my1">
                <label class="grd-row-col-2-6" for="{$property}">{$label}</label>
                <select class="grd-row-col-4-6" name="{$property}" id="{$property}">
                    <option value="">I don't know</option>
                    <option id="{$property}-yes" value="yes" {if isset($properties.$property) && $properties.$property == 'yes'}selected{/if}>Yes</option>
                    <option id="{$property}-only" value="only" {if isset($properties.$property) && $properties.$property == 'only'}selected{/if}>Only</option>
                    <option id="{$property}-no" value="no" {if isset($properties.$property) && $properties.$property == 'no'}selected{/if}>No</option>
                </select>
            </div>
        {/foreach}
    </div>
    <p><b>If in doubt</b>: only places that provide a proper choice for an option should be tagged <i>yes</i>. For instance, a restaurant with vegetarian starters but no vegetarian main course is to be tagged <i>no</i>. Same for, a pub that can't serve more than light snacks (baked potato, green salad) to vegetarians. </p>
    <input type="submit" value="Save" />
</form>
{include file="inc/footer.tpl"}
