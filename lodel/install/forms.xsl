<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
    <xsl:output method="html" encoding="ISO-8859-1" indent="yes"/>
    <xsl:param name="table"/>

    <!--                        -->
    <!-- Form header generation -->
    <!--                        -->

    <xsl:template match="/">
        <xsl:for-each select="//database/table[@name=$table]">
          <input type="hidden" name="do" value="edit" />
          <xsl:element name="input">
            <xsl:attribute name="type">hidden</xsl:attribute>
            <xsl:attribute name="name">table</xsl:attribute>
            <xsl:attribute name="value"><xsl:value-of select="@name"/></xsl:attribute>
          </xsl:element>
          <fieldset>
            <legend>
              <xsl:value-of select="@description"/>
            </legend>
            <xsl:apply-templates select="column"/>
          </fieldset>
        </xsl:for-each>
      </xsl:template>

    <!--                        -->
    <!-- Column                 -->
    <!--                        -->
    <!-- check wether the column-->
    <!-- has a condition of not -->

    <xsl:template match="column|vcolumn">
      <xsl:choose>
        <xsl:when test="@editcondition">
          <xsl:element name="IF">
            <xsl:attribute name="COND">
              <xsl:value-of select="@editcondition"/>
            </xsl:attribute>
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
    <!--                        -->

      <xsl:template name="column">        
        <xsl:choose>
            <!-- Hidden                 -->
            <xsl:when test="@primaryKey='true' or @visibility='hidden'">
                <xsl:element name="input">
                    <xsl:attribute name="type">hidden</xsl:attribute>
                    <xsl:attribute name="name">
                        <xsl:value-of select="@name"/>
                    </xsl:attribute>
                    <xsl:attribute name="value"><xsl:call-template name="lsvariable" /></xsl:attribute>
                </xsl:element>
                <xsl:call-template name="error" />
            </xsl:when>

            <!-- input type="text"                 -->
            <xsl:when test="@edittype='text' or @edittype='style' 
                            or @edittype='type' or @edittype='tplfile'">
                <p>
                  <xsl:call-template name="label" />
                    <xsl:element name="input">
                        <xsl:attribute name="type">text</xsl:attribute>
                        <xsl:attribute name="size">30</xsl:attribute>
                        <xsl:attribute name="name">
                            <xsl:value-of select="@name"/>
                        </xsl:attribute>
                        <xsl:attribute name="value"><xsl:call-template name="lsvariable" /></xsl:attribute>
                    </xsl:element>
                </p>
                <xsl:call-template name="error" />
            </xsl:when>


            <!-- input type="password"             -->
            <xsl:when test="@edittype='password'">
                <p>
                  <xsl:call-template name="label" />
                    <xsl:element name="input">
                        <xsl:attribute name="type">password</xsl:attribute>
                        <xsl:attribute name="size">30</xsl:attribute>
                        <xsl:attribute name="name">
                            <xsl:value-of select="@name"/>
                        </xsl:attribute>
                    </xsl:element>
                </p>
                <xsl:call-template name="error" />
            </xsl:when>

            <!-- textarea                          -->
            <xsl:when test="@edittype='longtext'">
                <p>
                  <xsl:call-template name="label" />
                    <xsl:element name="textarea">
                        <xsl:attribute name="size">30</xsl:attribute>
                        <xsl:attribute name="name">
                            <xsl:value-of select="@name"/>
                        </xsl:attribute>
                        <xsl:attribute name="row">10</xsl:attribute>
                        <xsl:attribute name="cols">60</xsl:attribute>
                            <xsl:call-template name="lsvariable" />
                    </xsl:element>
                </p>
                <xsl:call-template name="error" />
            </xsl:when>


            <!-- select and lang                   -->
            <xsl:when test="@edittype='select' or @edittype='lang'">
                <p>
                  <xsl:call-template name="label" />
                  <xsl:element name="select">
                        <xsl:attribute name="name">
                            <xsl:value-of select="@name"/>
                        </xsl:attribute>
                        <phptag>
                          makeSelect($context,"<xsl:value-of select="@name" />","<xsl:value-of select="$table" />","<xsl:value-of select="@edittype" />");
                        </phptag>
                    </xsl:element>
                 </p>
                 <xsl:call-template name="error" />
            </xsl:when>

            <xsl:when test="@edittype='special'">
                <p>
                  <phptag>
                    makeSpecialFormField($context,"<xsl:value-of select="@name" />","<xsl:value-of select="$table" />");
                  </phptag>
                </p>
            </xsl:when>
        </xsl:choose>
    </xsl:template>

    <!--                        -->
    <!-- Make the label         -->
    <!--                        -->

    <xsl:template name="label">
      <xsl:element name="label">
        <xsl:attribute name="for">
          <xsl:value-of select="@name"/>
        </xsl:attribute>
        <xsl:value-of select="@label"/>
        <xsl:if test="@required='true' and not(@edittype='select' or @editype='lang')">
          <span class="optional">(*)</span>
        </xsl:if>
        : 
      </xsl:element>        
    </xsl:template>

    <!--                        -->
    <!-- Make the erro          -->
    <!--                        -->

    <xsl:template name="error">
      <xsl:element name="LOOP">
        <xsl:attribute name="NAME">fielderror</xsl:attribute>
        <xsl:attribute name="FIELD"><xsl:value-of select="@name" /></xsl:attribute>
        <p class="error"><FUNC NAME="PRINT_ERROR_MESSAGE"/></p>
      </xsl:element>
    </xsl:template>


    <!--                        -->
    <!-- LS variable. Uppercase -->
    <!--                        -->

    <xsl:variable name="lowercase" select="'abcdefghijklmnopqrstuvwxyz'" />
    <xsl:variable name="uppercase" select="'ABCDEFGHIJKLMNOPQRSTUVWXYZ'" /> 
    <xsl:template name="lsvariable">[#<xsl:value-of select="translate(normalize-space(@name),$lowercase,$uppercase)"/>]</xsl:template>
</xsl:stylesheet>
