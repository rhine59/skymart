import SwiftUI

struct AccountView: View {
 @EnvironmentObject var account:AccountSession
 var body:some View {Group{if let user=account.user{SignedInAccountView(user:user)}else{LoginView()}}.navigationTitle("Account").overlay{if account.isLoading{ProgressView()}}}
}
private struct LoginView:View {
 @EnvironmentObject var account:AccountSession
 @State private var email="";@State private var password="";@State private var showReset=false
 var body:some View {Form{Section("Sign in to SkyMart"){TextField("Email",text:$email).textInputAutocapitalization(.never).keyboardType(.emailAddress);SecureField("Password",text:$password);Button("Sign in"){Task{await account.login(email:email,password:password)}}.disabled(email.isEmpty||password.isEmpty)};if let m=account.message{Section{Text(m)}};Section{Button("Forgot password?"){showReset=true}}}.sheet(isPresented:$showReset){NavigationStack{ResetRequestView(email:email)}}}
}
private struct ResetRequestView:View {
 @EnvironmentObject var account:AccountSession;@Environment(\.dismiss)var dismiss;@State var email:String
 var body:some View {Form{Section("Password reset"){TextField("Email",text:$email).keyboardType(.emailAddress).textInputAutocapitalization(.never);Text("We’ll send a time-limited reset link if the address is registered.").font(.caption).foregroundStyle(.secondary)};Section{Button("Send reset link"){Task{await account.requestReset(email:email)}}};if let m=account.message{Section{Text(m)}}}.navigationTitle("Reset password").toolbar{Button("Done"){dismiss()}}}
}
private struct SignedInAccountView:View {
 @EnvironmentObject var account:AccountSession;let user:APIUser;@State private var change=false
 var body:some View {List{Section("Profile"){Label(user.name,systemImage:"person.crop.circle.fill");Label(user.email,systemImage:"envelope")};Section("Security"){Button("Change password"){change=true}};Section{Button("Sign out",role:.destructive){Task{await account.logout()}}}}.sheet(isPresented:$change){NavigationStack{ChangePasswordView()}}}
}
private struct ChangePasswordView:View {
 @EnvironmentObject var account:AccountSession;@Environment(\.dismiss)var dismiss
 @State private var current="";@State private var new="";@State private var confirm=""
 var body:some View {Form{Section("Current password"){SecureField("Current password",text:$current)};Section("New password"){SecureField("New password",text:$new);SecureField("Repeat new password",text:$confirm);Text("Use at least 12 characters.").font(.caption).foregroundStyle(.secondary)};Section{Button("Change password"){Task{if await account.changePassword(current:current,new:new){dismiss()}}}.disabled(current.isEmpty||new.count<12||new != confirm)}}.navigationTitle("Change password").toolbar{Button("Cancel"){dismiss()}}}
}
