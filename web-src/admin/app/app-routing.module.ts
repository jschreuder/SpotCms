import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { DashboardComponent } from './dashboard/dashboard.component';
import { SiteContentComponent } from './site-content/site-content.component';
import { FileManagerComponent } from './file-manager/file-manager.component';
import { CreatePageComponent } from './create-page/create-page.component';

const routes: Routes = [
    { path: 'dashboard', component: DashboardComponent },
    { path: 'site-content', component: SiteContentComponent },
    { path: 'create-page', component: CreatePageComponent },
    { path: 'file-manager', component: FileManagerComponent },
    { path: '',   redirectTo: '/dashboard', pathMatch: 'full' },
];

@NgModule({
    imports: [RouterModule.forRoot(routes, { useHash: true })],
    exports: [RouterModule]
})
export class AppRoutingModule { }
