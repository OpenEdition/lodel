<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
	<xsl:output method="html" encoding="ISO-8859-1" indent="yes"/>
	<xsl:param name="table"/>

	<!--                        -->
	<!-- Form header generation -->
	<!--                        -->
	<xsl:template match="/">
		<xsl:for-each select="//database/table[@name=$table]|//database/vtable[@name=$table]">
			<input type="hidden" name="do" value="edit" />
			<xsl:element name="input">
				<xsl:attribute name="type">hidden</xsl:attribute>
				<xsl:attribute name="name">lo</xsl:attribute>
				<xsl:attribute name="value"><xsl:value-of select="@name"/></xsl:attribute>
			</xsl:element>
			<!--fieldset>
				<legend>
					<xsl:value-of select="@description"/>
				</legend-->
				<!--[xsl:element name="FUNC">
					<xsl:attribute name="NAME">PRINT_ERROR_LISTING_XSL_FORMS</xsl:attribute>
					<xsl:attribute name="TITLE"></xsl:attribute>
					</xsl:element]-->
				<xsl:apply-templates select="vgroup|column|vcolumn"/>
			<!--/fieldset-->
		</xsl:for-each>
	</xsl:template>

	<!--Vgroup -->
	<xsl:template match="vgroup">
		<fieldset>
			<legend><xsl:value-of select="@label" /></legend>
			<xsl:apply-templates select="column|vcolumn"/>
		</fieldset>
	</xsl:template>

	<!--                                                -->
	<!-- Column template match                          -->
	<!--                                                -->
	<!-- check wether the column has a condition or not -->
	<xsl:template match="column|vcolumn">
		<xsl:choose>
			<xsl:when test="@editcondition">
				<xsl:element name="IF">
					<xsl:attribute name="COND"><xsl:value-of select="@editcondition"/></xsl:attribute>
					<xsl:call-template name="column" />
				</xsl:element>
			</xsl:when>
			<xsl:otherwise>
				<xsl:call-template name="column" />
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<!--                        -->
	<!-- Make Column            -->
	<!--                        -->
	<xsl:template name="column">        
		<xsl:choose>
			<!-- champ cache -->
			<xsl:when test="@primaryKey='true' or @visibility='hidden'">
				<xsl:element name="input">
					<xsl:attribute name="type">hidden</xsl:attribute>
					<xsl:attribute name="name"><xsl:value-of select="@name"/></xsl:attribute>
					<xsl:attribute name="value"><xsl:call-template name="lsvariable" /></xsl:attribute>
				</xsl:element>
				<xsl:call-template name="error" />
			</xsl:when>

			<!-- input type="text" -->
			<xsl:when test="@edittype='mltext' or @edittype='image' or @edittype='file'">
				<xsl:call-template name="label" />
				<xsl:element name="FUNC">
					<xsl:attribute name="NAME">PRINT_EDIT_FIELD_XSLT</xsl:attribute>
					<xsl:attribute name="VARNAME"><xsl:value-of select="@name"/></xsl:attribute>
					<xsl:attribute name="TYPE"><xsl:value-of select="@edittype"/></xsl:attribute>
					<xsl:attribute name="VALUE"><xsl:call-template name="lsvariable" /></xsl:attribute>
					<xsl:attribute name="FULLVARNAME"><xsl:value-of select="@name"/></xsl:attribute>
				</xsl:element>
			</xsl:when>
			<xsl:when test="@edittype='text' or @edittype='style'  or @edittype='mlstyle' 
								or @edittype='type' or @edittype='tplfile' or @edittype='username'
								or @edittype='tablefield' or @edittype='class' or @edittype='email'
								or @edittype='url'">
				<xsl:call-template name="label" />
				<xsl:element name="input">
					<xsl:attribute name="type">text</xsl:attribute>
					<xsl:attribute name="size">
						<xsl:choose>
							<xsl:when test="@size &lt; 30"><xsl:value-of select="@size"/></xsl:when>
							<xsl:otherwise>30</xsl:otherwise>
						</xsl:choose>
					</xsl:attribute>
					<xsl:attribute name="name"><xsl:value-of select="@name"/></xsl:attribute>
					<xsl:attribute name="id"><xsl:value-of select="@name"/></xsl:attribute>
					<xsl:attribute name="class">text</xsl:attribute>
					<xsl:attribute name="value"><xsl:call-template name="lsvariable" /></xsl:attribute>
					<xsl:call-template name="javacheck" />
				</xsl:element>
				<xsl:call-template name="error" />
				<br />
			</xsl:when>

			<!-- mot de passe : input type="password" -->
			<xsl:when test="@edittype='passwd'">
				<xsl:call-template name="label" />
				<xsl:element name="input">
					<xsl:attribute name="type">password</xsl:attribute>
					<xsl:attribute name="size">30</xsl:attribute>
					<xsl:attribute name="name"><xsl:value-of select="@name"/></xsl:attribute>
					<xsl:attribute name="id"><xsl:value-of select="@name"/></xsl:attribute>
					<xsl:call-template name="javacheck" />
				</xsl:element>
				<xsl:call-template name="error" />
				<br />
			</xsl:when>

			<!-- cache a cocher : input type="checkbox" -->
			<xsl:when test="@edittype='boolean'">
				<xsl:call-template name="label" />
				<xsl:element name="IF">
					<xsl:choose>
						<xsl:when test="@name='protected'">
							<xsl:attribute name="COND">abs([#STATUS]) ge 32</xsl:attribute>
						</xsl:when>
						<xsl:otherwise>
							<xsl:attribute name="COND"><xsl:call-template name="lsvariable" /></xsl:attribute>
						</xsl:otherwise>
					</xsl:choose>
					<xsl:element name="input">
						<xsl:attribute name="type">checkbox</xsl:attribute>
						<xsl:attribute name="name"><xsl:value-of select="@name"/></xsl:attribute>
						<xsl:attribute name="id"><xsl:value-of select="@name"/></xsl:attribute>
						<xsl:attribute name="checked"></xsl:attribute>
						<xsl:call-template name="javacheck" />
					</xsl:element>
					<xsl:element name="ELSE"></xsl:element>
					<xsl:element name="input">
						<xsl:attribute name="type">checkbox</xsl:attribute>
						<xsl:attribute name="name"><xsl:value-of select="@name"/></xsl:attribute>
						<xsl:attribute name="id"><xsl:value-of select="@name"/></xsl:attribute>
						<xsl:call-template name="javacheck" />
					</xsl:element>
				</xsl:element>
				<xsl:call-template name="error" />
				<br />
			</xsl:when>

			<!-- zone de texte : textarea -->
			<xsl:when test="@edittype='longtext'">
				<xsl:call-template name="label" />
				<xsl:element name="textarea">
					<xsl:attribute name="size">30</xsl:attribute>
					<xsl:attribute name="name"><xsl:value-of select="@name"/></xsl:attribute>
					<xsl:attribute name="id"><xsl:value-of select="@name"/></xsl:attribute>
					<xsl:attribute name="class">text</xsl:attribute>
					<xsl:attribute name="row">10</xsl:attribute>
					<xsl:attribute name="cols">60</xsl:attribute>
					<xsl:call-template name="javacheck" />
					<xsl:call-template name="lsvariable" />
				</xsl:element>
					<xsl:call-template name="error" />
				<br />
			</xsl:when>


			<!-- champ select ou langue : select and lang -->
			<xsl:when test="@edittype='select' or @edittype='multipleselect' or @edittype='lang'">
				<xsl:call-template name="label" />
				<xsl:element name="select">
					<xsl:choose>
						<xsl:when test="@edittype='multipleselect'">
							<xsl:attribute name="multiple">multiple</xsl:attribute>
							<xsl:attribute name="name"><xsl:value-of select="@name"/>[]</xsl:attribute>
						</xsl:when>
						<xsl:otherwise>
							<xsl:attribute name="name"><xsl:value-of select="@name"/></xsl:attribute>
						</xsl:otherwise>
					</xsl:choose>
					<xsl:attribute name="id"><xsl:value-of select="@name"/></xsl:attribute>
					<xsl:call-template name="javacheck" />
					<phptag>
						makeSelect($context,"<xsl:value-of select="@name" />","<xsl:value-of select="$table" />","<xsl:value-of select="@edittype" />");
					</phptag>
				</xsl:element>
				<xsl:call-template name="error" />
				<br />
			</xsl:when>

			<!-- champ langue -->
			<xsl:when test="@edittype='select_lang'">
				<xsl:call-template name="label" />
				<xsl:element name="select">
					<xsl:attribute name="name"><xsl:value-of select="@name"/></xsl:attribute>
					<xsl:attribute name="id"><xsl:value-of select="@name"/></xsl:attribute>
					<xsl:call-template name="javacheck" />
					<phptag>
						makeSelectLang();
					</phptag>
				</xsl:element>
				<xsl:call-template name="error" />
				<br />
			</xsl:when>

			<!-- champ special -->
			<xsl:when test="@edittype='special'">
				<phptag>
					makeSpecialFormField($context,"<xsl:value-of select="@name" />","<xsl:value-of select="$table" />");
				</phptag>
				<br />
			</xsl:when>

			<xsl:otherwise>
				<xsl:if test="@label">
					<xsl:call-template name="label" />
					<span class="admin_display">
						<xsl:call-template name="lsvariable" />
					</span> 
					<br />
				</xsl:if>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<!--                        -->
	<!-- Make the label         -->
	<!--                        -->
	<xsl:template name="label">
		<xsl:element name="label">
			<xsl:attribute name="for"><xsl:value-of select="@name"/></xsl:attribute>
			<xsl:value-of select="@label"/>
			<xsl:if test="@required='true' and not(@edittype='select' or @edittype='lang' or @edittype='boolean')">
				<span class="optional">(*)</span>
			</xsl:if> : 
		</xsl:element>
		<br class="nobr" />
	</xsl:template>

	<!--                        -->
	<!-- Affichage de l'erreur  -->
	<!--                        -->
	<xsl:template name="error">
		<xsl:element name="LOOP">
			<xsl:attribute name="NAME">fielderror</xsl:attribute>
			<xsl:attribute name="FIELD"><xsl:value-of select="@name" /></xsl:attribute>
			<xsl:element name="a">
				<xsl:attribute name="id"><xsl:value-of select="@name" /></xsl:attribute>
			</xsl:element>
			<span class="error">
				<xsl:element name="FUNC">
					<xsl:attribute name="NAME">PRINT_ERROR_MESSAGE</xsl:attribute>
					<xsl:attribute name="TITLE"><xsl:value-of select="@label" /></xsl:attribute>
				</xsl:element>
			</span>
		</xsl:element>
	</xsl:template>

	<!--                           -->
	<!-- Verification javascript   -->
	<!--                           -->
	<xsl:template name="javacheck">
		<xsl:choose>
			<xsl:when test='@confirm'>
				<xsl:attribute name='onChange'><xsl:value-of select="@confirm" />();</xsl:attribute>
			</xsl:when>
		</xsl:choose>
	</xsl:template>

	<!--                        -->
	<!-- LS variable. Uppercase -->
	<!--                        -->
	<xsl:variable name="lowercase" select="'abcdefghijklmnopqrstuvwxyz'" />
		<xsl:variable name="uppercase" select="'ABCDEFGHIJKLMNOPQRSTUVWXYZ'" /> 
		<xsl:template name="lsvariable">[#<xsl:value-of select="translate(normalize-space(@name),$lowercase,$uppercase)"/>]</xsl:template>


<!-- Fin -->
</xsl:stylesheet>