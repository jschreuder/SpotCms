import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { DashboardComponent } from './dashboard/dashboard.component';
import { SiteContentComponent } from './site-content/site-content.component';

const routes: Routes = [
    { path: 'dashboard', component: DashboardComponent },
    { path: 'site-content', component: SiteContentComponent },
    { path: '',   redirectTo: '/dashboard', pathMatch: 'full' },
];

@NgModule({
    imports: [RouterModule.forRoot(routes, { useHash: true })],
    exports: [RouterModule]
})
export class AppRoutingModule { }
