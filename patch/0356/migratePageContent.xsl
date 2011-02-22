<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:change="http://www.rbs.fr/change/1.0/schema" version="1.0">
	<xsl:output indent="no" method="xml" omit-xml-declaration="no"
		cdata-section-elements="change:richtextcontent" />

	<xsl:template match="node()|@*">
		<xsl:copy>
			<xsl:apply-templates select="@*|node()" />
		</xsl:copy>
	</xsl:template>

	<xsl:template
		match="change:block[@type = 'modules_catalog_productCrossSelling']">
		<change:block>
			<xsl:for-each select="@*">
				<xsl:choose>
					<xsl:when test="name(.) = '__type'">
						<xsl:if test=". != ''">
							<xsl:attribute name="__crossSellingType"><xsl:value-of
								select="." /></xsl:attribute>
						</xsl:if>
					</xsl:when>
					<xsl:otherwise>
						<xsl:attribute name="{name()}"><xsl:value-of
							select="." /></xsl:attribute>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:for-each>
		</change:block>
	</xsl:template>
</xsl:stylesheet>