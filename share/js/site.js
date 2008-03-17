function recomposeMail(obj, region, nom, domaine)
{
	obj.href = 'mailto:' + nom + '@' + domaine + '.' + region;
	obj.onclick = (function() {});
}
