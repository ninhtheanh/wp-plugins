function fb_share_feed_dialog()
{
    var share_link = jQuery("meta[property='og:url']").attr("content");
    var picture = jQuery("meta[property='og:image']").attr("content");
    var name = jQuery("meta[property='og:title']").attr("content");
    var caption = '';
    var description = jQuery("meta[property='og:description']").attr("content");

    var obj = {
        method: 'feed',
        link: share_link,
        picture: picture,
        name: name,
        caption: caption,
        description: description,
        actions: [{ name: name, link: share_link }]
    };
    FB.ui(obj);
}