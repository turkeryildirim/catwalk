// This file is autogenerated – run `ant apidocs` to update it

import {
    Configuration as FrontendCellConfiguration,
} from '../../frontend/cell/'

import {
    Tastic as FrontendTastic,
} from '../../frontend/'

export interface ActionContext {
     frontasticContext?: Context;
}

export interface Context {
     environment?: string;
     project: Project;
     projectConfiguration: any;
     locale: string;
     featureFlags?: Map<string, boolean>;
}

export interface DataSourceConfiguration {
     dataSourceId: string;
     type: string;
     name: string;
     configuration: any;
}

export interface DataSourceContext {
     frontasticContext?: Context;
     pageFolder?: PageFolder;
     page?: Page;
     usingTastics?: FrontendTastic[] | null;
     request?: Request;
}

export interface DataSourceResult {
     dataSourcePayload?: any;
}

export interface DynamicPageRedirectResult {
     redirectLocation?: any;
     statusCode?: any;
     statusMessage?: string;
     additionalResponseHeaders?: Map<string, string>;
}

export interface DynamicPageSuccessResult {
     dynamicPageType?: string;
     dataSourcePayload?: any;
     pageMatchingPayload?: any;
}

export interface LayoutElement {
     layoutElementId: string;
     configuration: FrontendCellConfiguration;
     tastics: FrontendTastic[];
}

export interface Page {
     pageId: string;
     sections: Section[];
     state: string;
}

export interface PageFolder {
     pageFolderId: string;
     isDynamic: boolean;
     pageFolderType: string;
     configuration: any;
     dataSourceConfigurations: DataSourceConfiguration[];
     name?: string;
     ancestorIdsMaterializedPath: string;
     depth?: number;
     sort: number;
}

export interface Project {
     projectId: string;
     name: string;
     customer: string;
     publicUrl: string;
     configuration: any;
     locales: string[];
     defaultLocale: string;
}

export interface Request {
     body?: string;
     cookies?: Map<string, string>;
     hostname?: string;
     method?: string;
     path?: string;
     query?: string;
     sessionData?: any;
}

export interface Section {
     sectionId: string;
     layoutElements?: LayoutElement[];
}
